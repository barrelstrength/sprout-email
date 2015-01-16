<?php
namespace Craft;

class SproutEmailPlugin extends BasePlugin
{
	public function getName()
	{
		$alias = $this->getSettings()->getAttribute('pluginNameOverride');

		return $alias ? $alias : Craft::t('Sprout Email');
	}

	public function getVersion()
	{
		return '0.8.2';
	}

	public function getDeveloper()
	{
		return 'Barrel Strength Design';
	}

	public function getDeveloperUrl()
	{
		return 'http://barrelstrengthdesign.com';
	}

	public function hasCpSection()
	{
		return (craft()->userSession->isAdmin() or craft()->userSession->user->can('manageEmail'));
	}

	protected function defineSettings()
	{
		return array (
			'pluginNameOverride' => AttributeType::String
		);
	}

	public function registerUserPermissions()
	{
		return array(
			'manageEmail'	=> array(
				'label' => Craft::t('Manage Email Section')
			),
			'editSproutFormsSettings'	=> array(
				'label' => Craft::t('Edit Form Settings')
			)
		);
	}

	public function registerCpRoutes()
	{
		return array(
			'sproutemail/settings/campaigns/edit/(?P<campaignId>\d+|new)(/(template|recipients|fields))?' => array(
				'action' => 'sproutEmail/campaign/campaignSettingsTemplate'
			),
			'sproutemail/settings/notifications/edit/(?P<campaignId>\d+|new)(/(template|recipients|fields))?' => array(
				'action' => 'sproutEmail/notifications/notificationSettingsTemplate'
			),
			'sproutemail/entries/new' => array(
				'action' => 'sproutEmail/entry/editEntryTemplate'
			),
			'sproutemail/entries/edit/(?P<entryId>\d+)' => array(
				'action' => 'sproutEmail/entry/editEntryTemplate'
			),
			'sproutemail/entries/(?P<campaignId>\d+)/new' => array(
				'action' => 'sproutEmail/entry/editEntryTemplate'
			),
			'sproutemail/settings' => array(
				'action' => 'sproutEmail/settingsIndexTemplate'
			),
			'sproutemail/examples' => 'sproutemail/_cp/examples',
			'sproutemail/events/new' => 'sproutemail/events/_edit',

			'sproutemail/events/edit/(?P<eventId>\d+)' => 'sproutemail/events/_edit',
		);
	}

	public function registerSiteRoutes()
	{
		return array(
			't' => array('action' => 'sproutEmail/sandbox')
		);
	}

	public function addTwigExtension()
	{
	    Craft::import('plugins.sproutemail.twigextensions.SproutEmailTwigExtension');

	    return new SproutEmailTwigExtension();
	}

	public function init()
	{
		parent::init();

		require_once dirname(__FILE__).'/vendor/autoload.php';
		Craft::import('plugins.sproutemail.enums.*');
		Craft::import('plugins.sproutemail.contracts.*');
		Craft::import('plugins.sproutemail.interfaces.*');
		Craft::import('plugins.sproutemail.integrations.mailers.*');
		Craft::import('plugins.sproutemail.integrations.sproutemail.*');

		sproutEmail()->notifications->registerDynamicEventHandler();
	}

	/**
	 * Anonymous function for plugin integration
	 *
	 * @return function
	 */
	private function _getClosure()
	{
		/**
		 * Event handler closure
		 *
		 * @var String [required] - event fired
		 * @var BaseModel [required] - the entity to be used for data extraction
		 * @var Bool [optional] - event status; if passed, the function will exit on false and process on true; defaults to true
		 */
		return function ($event, $entity = null, $success = TRUE)
		{
			// if ! $success, return
			if ( ! $success )
			{
				return false;
			}

			// an event can be either an Event object or a string
			if ( $event instanceof Event )
			{
				$event_name = $event->params ['event'];
			}
			else
			{
				$event_name = ( string ) $event;
			}

			// check if entity is passed in as an event param
			if ( ! $entity && isset( $event->params ['entity'] ) )
			{
				$entity = $event->params ['entity'];
			}

			// validate
			$criteria = new \CDbCriteria();
			$criteria->condition = 'event=:event';
			$criteria->params = array (
					':event' => $event_name
			);

			if ( ! $event_notification = SproutEmail_NotificationEventRecord::model()->find( $criteria ) )
			{
				return false;
			}

			// process $entity
			// get registered entries
			if ( $res = sproutEmail()->notifications->getNotifications( $event_name, $entity ) )
			{
				foreach ( $res as $campaign )
				{
					if ( ! $campaign->recipients )
					{
						return false;
					}

					// set $_POST vars
					if ( $post = craft()->request->getPost() )
					{
						foreach ( $post as $key => $val )
						{
							if ( is_object( $entity ) && property_exists($entity, $key) )
							{
								$entity->{$key} = $val;
							}
							else if ( is_array( $entity ) )
							{
								$entity [$key] = $val;
							}
						}
					}

					$opts = json_decode( json_encode( $event_notification->options, false ) );

					if ( $opts && $opts->handler )
					{
						list ( $class, $function ) = explode( '::', $opts->handler );

						$options = $campaign->campaignNotificationEvent [0]->options;

						$base_classes = json_decode( $opts->handler_base_classes );

						if ( $base_classes && ! empty( $base_classes ) )
						{
							foreach ( $base_classes as $base )
							{
								require_once ($base);
							}
						}

						require_once ($opts->handler_location);

						$obj = new $class();
						if ( ! method_exists( $obj, $function ) )
						{
							return false;
						}

						if ( ! $obj->$function( $event_name, $entity, $options ) )
						{
							return true;
						}
					}

					try
					{
						$campaign->subject = craft()->templates->renderString( $campaign->subject, array (
								'entry' => $entity
						) );
					}
					catch ( \Exception $e )
					{
						$campaign->subject = str_replace( '{{', '', $campaign->subject );
						$campaign->subject = str_replace( '}}', '', $campaign->subject );
					}

					try
					{
						$campaign->replyToEmail = craft()->templates->renderString( $campaign->replyToEmail, array (
								'entry' => $entity
						) );
					}
					catch ( \Exception $e )
					{
						$campaign->replyToEmail = null;
					}

					$recipientLists = array ();
					foreach ( $campaign->recipientList as $list )
					{
						$recipientLists [] = $list->emailProviderRecipientListId;
					}

					$service = 'sproutEmail_' . lcfirst( $campaign->emailProvider );
					craft()->{$service}->sendCampaign( $campaign, $recipientLists );
				}
			}
		};
	}

	/**
	 * Handle system event
	 *
	 * @param string $eventType
	 * @param obj $entry
	 * @return boolean
	 */
	private function _processEvent($eventType, $entry)
	{
		// get registered entries
		if ( $res = craft()->sproutEmail_notifications->getEventNotifications( $eventType, $entry ) )
		{
			foreach ( $res as $campaign )
			{
				if ( ! $campaign->recipients && ! $campaign->recipientList )
				{
					return false;
				}
				try
				{
					$campaign->subject = craft()->templates->renderString( $campaign->subject, array (
							'entry' => $entry
					) );
				}
				catch ( \Exception $e )
				{
					$campaign->subject = str_replace( '{{', '', $campaign->subject );
					$campaign->subject = str_replace( '}}', '', $campaign->subject );
				}

				try
				{
					$campaign->fromName = craft()->templates->renderString( $campaign->fromName, array (
							'entry' => $entry
					) );
				}
				catch ( \Exception $e )
				{
					$campaign->fromName = str_replace( '{{', '', $campaign->fromName );
					$campaign->fromName = str_replace( '}}', '', $campaign->fromName );
				}

				try
				{
					$campaign->replyToEmail = craft()->templates->renderString( $campaign->replyToEmail, array (
							'entry' => $entry
					) );
				}
				catch ( \Exception $e )
				{
					$campaign->replyToEmail = null;
				}

				try
				{
					$campaign->recipients = craft()->templates->renderString( $campaign->recipients, array (
							'entry' => $entry
					) );
				}
				catch ( \Exception $e )
				{
					$campaign->recipients = null;
				}

				$service = 'sproutEmail_' . lcfirst( $campaign->emailProvider );

				craft()->{$service}->sendCampaign( $campaign );
			}
		}
	}

	/**
	 * Using our own API to register native Craft events
	 *
	 * @return array
	 */
	public function defineSproutEmailEvents()
	{
		return array(
			'entries.saveEntry' => new SproutEmail_EntriesSaveEntryEvent(),
			'userSession.login' => new SproutEmail_UserSessionLoginEvent(),
		);
	}

	public function defineSproutEmailMailers()
	{
		return array(
//			'mailgun'	    => new SproutEmail_MailGunMailer(),
//			'copypaste'		=> new SproutEmail_CopyPasteMailer(),
//			'sproutemail'	=> new SproutEmail_SproutEmailMailer(),
		);
	}

	public function onBeforeInstall()
	{
		Craft::import('plugins.sproutemail.enums.Campaign');
	}

	public function onAfterInstall()
	{
		try
		{
			if (!$this->getIsInitialized())
			{
				$this->init();
			}

			sproutEmail()->mailers->installMailers();
		}
		catch(\Exception $e) {}
	}
}

/**
 * @return SproutEmailService
 */
function sproutEmail()
{
	return Craft::app()->getComponent('sproutEmail');
}
