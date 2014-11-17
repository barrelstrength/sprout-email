<?php
namespace Craft;

class SproutEmailPlugin extends BasePlugin
{
	public function __construct()
	{
		require_once 'helpers/CssToInlineStyles/CssToInlineStyles.php';
	}

	public function getName()
	{
		$pluginName = Craft::t( 'Sprout Email' );
		
		// The plugin name override
		$plugin = craft()->db->createCommand()->select( 'settings' )->from( 'plugins' )->where( 'class=:class', array (
			':class' => 'SproutEmail' 
		) )->queryScalar();
		
		$plugin = json_decode( $plugin, true );
		$pluginNameOverride = $plugin ['pluginNameOverride'];
		
		return ($pluginNameOverride) ? $pluginNameOverride : $pluginName;
	}
	
	public function getVersion()
	{
		return '0.7.14';
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
		if (craft()->userSession->isAdmin() or craft()->userSession->user->can('manageEmail'))
		{
			return true;
		}
		else
		{
			return false;
		}
		
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
	
	/**
	 * Register control panel routes
	 */
	public function registerCpRoutes()
	{
		return array (

			/*
			 * Edit Campaign Settings
			 * @controller SproutEmail_CampaignController
			 * @method     actionCampaignSettingsTemplate
			 * @template   sproutemail/templates/settings/campaigns/_edit.html
			 */
			'sproutemail/settings/campaigns/edit\/(?P<campaignId>\d+|new)(/(template|recipients|fields))?' => array(
				'action' => 'sproutEmail/campaign/campaignSettingsTemplate'
			),

			/*
			 * Edit Notification Settings
			 * @controller SproutEmail_NotificationsController
			 * @method     actionNotificationSettingsTemplate
			 * @template   sproutemail/templates/settings/notifications/_edit.html
			 */
			'sproutemail/settings/notifications/edit\/(?P<campaignId>\d+|new)(/(template|recipients|fields))?' => array(
				'action' => 'sproutEmail/notifications/notificationSettingsTemplate'
			),

			/*
			 * Create New Email
			 * @controller SproutEmail_EntryController
			 * @method     actionEditEntryTemplate
			 * @template   sproutemail/templates/entries/_edit.html
			 */
			'sproutemail/entries/new' => array(
				'action' => 'sproutEmail/entry/editEntryTemplate'
			),

			'sproutemail/entries/edit/(?P<entryId>\d+)' => array(
				'action' => 'sproutEmail/entry/editEntryTemplate'
			),

			/*
			 * Create New Email
			 * @controller SproutEmail_EntryController
			 * @method     actionEditEntryTemplate
			 * @template   sproutemail/templates/entries/_edit.html
			 */
			'sproutemail/entries/(?P<campaignId>\d)/new' => array(
				'action' => 'sproutEmail/entry/editEntryTemplate'
			),

			/*
			 * Edit Settings
			 * @controller SproutEmail_SettingsController
			 * @method     actionSettingsIndexTemplate
			 * @template   sproutemail/templates/settings/index.html
			 */
			'sproutemail/settings' => array(
				'action' => 'sproutEmail/settings/settingsIndexTemplate'
			),

			/*
			 * Example Form installation page
			 */
			'sproutemail/examples' => 
			'sproutemail/_cp/examples',

			// ------------------------------------------------------------

			'sproutemail/events/new' => 
			'sproutemail/events/_edit',
			
			'sproutemail/events/edit/(?P<eventId>\d+)' => 
			'sproutemail/events/_edit',
		);
	}

	/**
	 * Register twig extension
	 */
	public function addTwigExtension()
	{
	    Craft::import('plugins.sproutemail.twigextensions.SproutEmailTwigExtension');

	    return new SproutEmailTwigExtension();
	}
	
	/**
	 * Add default events after plugin is installed
	 */
	public function onAfterInstall()
	{
		$events = array (
				
			// @TODO - At some point, consider making all hooks available, just make
			// sure that less common ones don't crowd out the primary interface

			// Content
			array (
				'registrar' => 'craft',
				'event' => 'entries.saveEntry.new',
				'description' => 'Craft: When a new entry is created' 
			),
			array (
				'registrar' => 'craft',
				'event' => 'entries.saveEntry',
				'description' => 'Craft: When an existing entry is updated' 
			),
			array (
				'registrar' => 'craft',
				'event' => 'assets.onSaveAsset',
				'description' => 'Craft: When an asset is saved' 
			),
			array (
				'registrar' => 'craft',
				'event' => 'content.saveContent',
				'description' => 'Craft: When content is saved' 
			),
			array (
				'registrar' => 'craft',
				'event' => 'globals.saveGlobalContent',
				'description' => "Craft: When a global set's content is saved" 
			),
			array (
				'registrar' => 'craft',
				'event' => 'tags.saveTag',
				'description' => 'Craft: When a tag is saved' 
			),
			array (
				'registrar' => 'craft',
				'event' => 'tags.saveTagContent',
				'description' => 'Craft: When tag content is saved' 
			),

			// Users
			array (
				'registrar' => 'craft',
				'event' => 'users.saveUser',
				'description' => 'Craft: When a user is saved' 
			),
			array (
				'registrar' => 'craft',
				'event' => 'users.activateUser',
				'description' => 'Craft: When a user is activated' 
			),
			array (
				'registrar' => 'craft',
				'event' => 'users.unlockUser',
				'description' => 'Craft: When a user is unlocked' 
			),
			array (
				'registrar' => 'craft',
				'event' => 'users.suspendUser',
				'description' => 'Craft: When a user is suspended' 
			),
			array (
				'registrar' => 'craft',
				'event' => 'users.unsuspendUser',
				'description' => 'Craft: When a user is unsuspended' 
			),
			array (
				'registrar' => 'craft',
				'event' => 'users.deleteUser',
				'description' => 'Craft: When a user is deleted' 
			),
			array (
				'registrar' => 'craft',
				'event' => 'userSession.login',
				'description' => 'Craft: When a user logs in' 
			),

			// Updates
			array (
				'registrar' => 'craft',
				'event' => 'updates.beginUpdate',
				'description' => 'Craft: When an update is started' 
			),
			array (
				'registrar' => 'craft',
				'event' => 'updates.endUpdate',
				'description' => 'Craft: When an update is finished' 
			),

			// Other
			array (
				'registrar' => 'craft',
				'event' => 'cron',
				'description' => 'Send email via Cron Job'
			),
		);
		
		foreach ( $events as $event )
		{
			craft()->db->createCommand()->insert( 'sproutemail_notificationevents', $event );
		}
		
		$providers = array (
			array (
				'emailProvider' => 'CampaignMonitor',
				'apiSettings' => '{"clientId":"","apiKey":""}'
			),
			array (
				'emailProvider' => 'MailChimp',
				'apiSettings' => '{"apiKey":""}',
				'dateCreated' => '2014-03-10 21:00:00' 
			),
			array (
				'emailProvider' => 'SendGrid',
				'apiSettings' => '{"apiUser":"","apiKey":""}'
			),
			array (
				'emailProvider' => 'MailGun',
				'apiSettings' => '{"apiUser":"","domain":""}'
			),
			array (
				'emailProvider' => 'CopyPaste',
				'apiSettings' => '{"enabled":0}'
			)
		);
		
		foreach ( $providers as $provider )
		{
			craft()->db->createCommand()->insert( 'sproutemail_providersettings', $provider );
		}

		/**
	 	 * Install optional examples after installation
	 	 */
		craft()->request->redirect(UrlHelper::getCpUrl() . '/sproutemail/examples');

	}
	
	/**
	 * Initialize
	 *
	 * @return void
	 */
	public function init()
	{	
		parent::init();

		Craft::import('plugins.sproutemail.enums.Campaign');

		// events fired by $this->raiseEvent
		craft()->on( 'entries.saveEntry', array($this,'onSaveEntry'));
		craft()->on( 'assets.saveAsset', array($this,'onSaveAsset'));
		craft()->on( 'content.saveContent', array($this,'onSaveContent'));
		craft()->on( 'globals.saveGlobalContent', array($this,'onSaveGlobalContent'));
		craft()->on( 'tags.saveTag', array($this,'onSaveTag'));
		craft()->on( 'tags.saveTagContent', array($this,'onSaveTagContent'));
		
		craft()->on( 'users.saveUser', array($this,'onSaveUser'));
		craft()->on( 'users.activateUser', array($this,'onActivateUser'));
		craft()->on( 'users.unlockUser', array($this,'onUnlockUser'));
		craft()->on( 'users.suspendUser', array($this,'onSuspendUser'));
		craft()->on( 'users.unsuspendUser', array($this,'onUnsuspendUser'));
		craft()->on( 'users.deleteUser', array($this,'onDeleteUser'));
		craft()->on( 'userSession.login', array($this,'onLogin'));
		
		craft()->on( 'updates.beginUpdate', array($this,'onBeginUpdate'));
		craft()->on( 'updates.endUpdate', array($this,'onEndUpdate'));
		
		$criteria = new \CDbCriteria();
		$criteria->condition = 'registrar!=:registrar';
		$criteria->params = array (
				':registrar' => 'craft' 
		);
		if ( $events = SproutEmail_NotificationEventRecord::model()->findAll( $criteria ) )
		{
			foreach ( $events as $event )
			{
				try
				{
					craft()->plugins->call( $event->registrar, array (
							$event->event,
							$this->_getClosure() 
					) );
				}
				catch ( \Exception $e )
				{
					die( $e->getMessage() );
				}
			}
		}
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
			if ( $res = craft()->sproutEmail_notifications->getEventNotifications( $event_name, $entity ) )
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
	 * Available variables:
	 * all entries in 'craft_content' table
	 * to access: entry.id, entry.body, entry.locale, etc.
	 *
	 * @param Event $event            
	 */
	public function onSaveEntry(Event $event)
	{
		switch ($event->params ['isNewEntry'])
		{
			case true :
				$event_type = 'entries.saveEntry.new';
				break;
			default :
				$event_type = 'entries.saveEntry';
				break;
		}
		$this->_processEvent( $event_type, $event->params ['entry'] );
	}
	
	/**
	 * Available variables:
	 * all properties of Craft\AssetFileRecord
	 * to access: entry.filename
	 *
	 * @param Event $event            
	 */
	public function onSaveAsset(Event $event)
	{
		$this->_processEvent( 'assets.saveAsset', $event->params ['asset'] );
	}
	
	/**
	 * Available variables:
	 * all entries in 'craft_content' table
	 * to access: entry.id, entry.body, entry.locale, etc.
	 *
	 * @param Event $event            
	 */
	public function onSaveContent(Event $event)
	{
		switch ($event->params ['isNewContent'])
		{
			case true :
				$event_type = 'content.saveContent.new';
				break;
			default :
				$event_type = 'content.saveContent';
				break;
		}
		$this->_processEvent( $event_type, $event->params ['content'] );
	}
	
	/**
	 * Available variables:
	 * all properties of Craft\GlobalSetModel
	 * to access: entry.id
	 *
	 * @param Event $event            
	 */
	public function onSaveGlobalContent(Event $event)
	{
		$this->_processEvent( 'globals.saveGlobalContent', $event->params ['globalSet'] );
	}
	
	/**
	 * Available variables:
	 * all properties of Craft\TagRecord
	 * to access: entry.id
	 *
	 * @param Event $event            
	 */
	public function onSaveTag(Event $event)
	{
		$this->_processEvent( 'tags.saveTag', $event->params ['tag'] );
	}
	
	/**
	 * Available variables:
	 * all properties of Craft\TagRecord
	 * to access: entry.id
	 *
	 * @param Event $event            
	 */
	public function onSaveTagContent(Event $event)
	{
		$this->_processEvent( 'tags.saveTagContent', $event->params ['tag'] );
	}
	
	/**
	 * Available variables:
	 * all entries in 'craft_users' table
	 * to access: entry.id, entry.firstName, etc.
	 *
	 * @param Event $event            
	 */
	public function onSaveUser(Event $event)
	{
		$this->_processEvent( 'users.saveUser', $event->params ['user'] );
	}
	
	/**
	 * Available variables:
	 * all entries in 'craft_users' table
	 * to access: entry.id, entry.firstName, etc.
	 *
	 * @param Event $event            
	 */
	public function onActivateUser(Event $event)
	{
		$this->_processEvent( 'users.activateUser', $event->params ['user'] );
	}
	
	/**
	 * Available variables:
	 * all entries in 'craft_users' table
	 * to access: entry.id, entry.firstName, etc.
	 *
	 * @param Event $event            
	 */
	public function onUnlockUser(Event $event)
	{
		$this->_processEvent( 'users.unlockUser', $event->params ['user'] );
	}
	
	/**
	 * Available variables:
	 * all entries in 'craft_users' table
	 * to access: entry.id, entry.firstName, etc.
	 *
	 * @param Event $event            
	 */
	public function onSuspendUser(Event $event)
	{
		$this->_processEvent( 'users.suspendUser', $event->params ['user'] );
	}
	/**
	 * Available variables:
	 * all entries in 'craft_users' table
	 * to access: entry.id, entry.firstName, etc.
	 *
	 * @param Event $event            
	 */
	public function onUnsuspendUser(Event $event)
	{
		$this->_processEvent( 'users.unsuspendUser', $event->params ['user'] );
	}
	
	/**
	 * Available variables:
	 * all entries in 'craft_users' table
	 * to access: entry.id, entry.firstName, etc.
	 *
	 * @param Event $event            
	 */
	public function onDeleteUser(Event $event)
	{
		$this->_processEvent( 'users.deleteUser', $event->params ['user'] );
	}
	
	/**
	 * Available variables:
	 * username
	 * to access: entry.username
	 *
	 * @param Event $event            
	 */
	public function onLogin(Event $event)
	{
		$this->_processEvent( 'userSession.login', array (
				'username' => $event->params ['username'] 
		) );
	}
	
	/**
	 * Available variables:
	 * type (manual or auto)
	 * to access: entry.type
	 *
	 * @param Event $event            
	 */
	public function onBeginUpdate(Event $event)
	{
		$this->_processEvent( 'updates.beginUpdate', $event->params ['type'] );
	}
	
	/**
	 * Available variables:
	 * success (bool)
	 * to access: entry.success
	 *
	 * @param Event $event            
	 */
	public function onEndUpdate(Event $event)
	{
		$this->_processEvent( 'updates.endUpdate', $event->params ['success'] );
	}
	
	/**
	 * Available variables:
	 * all entries in 'craft_users' table
	 * to access: entry.id, entry.firstName, etc.
	 *
	 * @param Event $event            
	 */
	public function onBeforeSaveUser(Event $event)
	{
		$this->_processEvent( 'users.beforeSaveUser', $event->params ['user'] );
	}
	
	/**
	 * Available variables:
	 * all entries in 'craft_users' table
	 * to access: entry.id, entry.firstName, etc.
	 *
	 * @param Event $event            
	 */
	public function onBeforeUnlockUser(Event $event)
	{
		$this->_processEvent( 'users.beforeUnlockUser', $event->params ['user'] );
	}
	
	/**
	 * Available variables:
	 * all entries in 'craft_users' table
	 * to access: entry.id, entry.firstName, etc.
	 *
	 * @param Event $event            
	 */
	public function onBeforeUnsuspendUser(Event $event)
	{
		$this->_processEvent( 'users.beforeUnsuspendUser', $event->params ['user'] );
	}
	
	/**
	 * Available variables:
	 * all entries in 'craft_users' table
	 * to access: entry.id, entry.firstName, etc.
	 *
	 * @param Event $event            
	 */
	public function onBeforeSuspendUser(Event $event)
	{
		$this->_processEvent( 'users.beforeSuspendUser', $event->params ['user'] );
	}
	
	/**
	 * Available variables:
	 * all entries in 'craft_users' table
	 * to access: entry.id, entry.firstName, etc.
	 *
	 * @param Event $event            
	 */
	public function onBeforeDeleteUser(Event $event)
	{
		$this->_processEvent( 'users.beforeDeleteUser', $event->params ['user'] );
	}
	
	/**
	 * Available variables:
	 * all entries in 'craft_users' table
	 * to access: entry.id, entry.firstName, etc.
	 *
	 * @param Event $event            
	 */
	public function onBeforeVerifyUser(Event $event)
	{
		$this->_processEvent( 'users.beforeVerifyUser', $event->params ['user'] );
	}
	
	/**
	 * Available variables:
	 * all entries in 'craft_users' table
	 * to access: entry.id, entry.firstName, etc.
	 *
	 * @param Event $event            
	 */
	public function onBeforeActivateUser(Event $event)
	{
		$this->_processEvent( 'users.beforeActivateUser', $event->params ['user'] );
	}
	
	/**
	 * Available variables:
	 * username
	 * to access: entry.username
	 *
	 * @param Event $event            
	 */
	public function onBeforeLogin(Event $event)
	{
		$this->_processEvent( 'userSession.beforeLogin', array (
				'username' => $event->params ['username'] 
		) );
	}
	
	/**
	 * Available variables:
	 * none
	 *
	 * @param Event $event            
	 */
	public function onLoadPlugins(Event $event)
	{
		$this->_processEvent( 'plugins.loadPlugins', null );
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
				
				// @TODO - probably want to tighten up this code. Would it
				// be better to switch to do a string replace and only make
				// key variables available here?
				// entry.author, entry.author.email, entry.title
				
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
}