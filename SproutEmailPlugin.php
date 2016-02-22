<?php
namespace Craft;

/**
 * Class SproutEmailPlugin
 *
 * @package Craft
 */
class SproutEmailPlugin extends BasePlugin
{
	/**
	 * @return string
	 */
	public function getName()
	{
		$alias = $this->getSettings()->getAttribute('pluginNameOverride');

		return $alias ? $alias : Craft::t('Sprout Email');
	}

	/**
	 * @return string
	 */
	public function getDescription()
	{
		return 'Flexible, integrated email marketing and notifications.';
	}

	/**
	 * @return string
	 */
	public function getVersion()
	{
		return '2.1.3';
	}

	/**
	 * @return string
	 */
	public function getSchemaVersion()
	{
		return '2.0.2';
	}

	/**
	 * @return string
	 */
	public function getDeveloper()
	{
		return 'Barrel Strength Design';
	}

	/**
	 * @return string
	 */
	public function getDeveloperUrl()
	{
		return 'http://barrelstrengthdesign.com';
	}

	/**
	 * @return string
	 */
	public function getDocumentationUrl()
	{
		return 'http://sprout.barrelstrengthdesign.com/craft-plugins/email/docs';
	}

	/**
	 * @return string
	 */
	public function getReleaseFeedUrl()
	{
		return 'https://sprout.barrelstrengthdesign.com/craft-plugins/email/releases.json';
	}

	/**
	 * @return bool
	 */
	public function hasCpSection()
	{
		return (craft()->userSession->isAdmin() or craft()->userSession->user->can('accessPlugin-SproutEmail'));
	}

	/**
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'pluginNameOverride' => AttributeType::String
		);
	}

	/**
	 * Get Settings URL
	 */
	public function getSettingsUrl()
	{
		return 'sproutemail/settings';
	}

	/**
	 * @return array
	 */
	public function registerUserPermissions()
	{
		return array(
			'editSproutEmailSettings' => array(
				'label' => Craft::t('Edit Settings')
			)
		);
	}

	/**
	 * @return array
	 */
	public function registerCpRoutes()
	{
		$url         = 'sproutemail';
		$ctrl        = 'sproutEmail/defaultMailer';
		$recipients  = $url.'/recipients';
		$emailClient = array(
			$recipients                       => array('action' => $ctrl.'/showIndexRecipientTemplate'),
			$recipients.'/new'                => array('action' => $ctrl.'/showEditRecipientTemplate'),
			$recipients.'/edit/(?P<id>[\d]+)' => array('action' => $ctrl.'/showEditRecipientTemplate'),
		);

		return array_merge($emailClient, array(
			'sproutemail/settings/(?P<settingsTemplate>mailers)/(?P<mailerId>[a-z]+)' => array(
				'action' => 'sproutEmail/mailer/editSettings'
			),
			'sproutemail/settings/(?P<settingsTemplate>campaigns)/edit/(?P<campaignId>\d+|new)(/(template|recipients|fields))?' => array(
				'action' => 'sproutEmail/campaign/campaignSettingsTemplate'
			),
			'sproutemail/settings/(?P<settingsTemplate>notifications)/edit/(?P<campaignId>\d+|new)(/(template|recipients|fields))?' => array(
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
			'sproutemail/settings/(?P<settingsTemplate>.*)' => array(
					'action' => 'sproutEmail/settingsIndexTemplate'
			),

			'sproutemail/events/new' =>
			'sproutemail/events/_edit',

			'sproutemail/events/edit/(?P<eventId>\d+)' =>
			'sproutemail/events/_edit',

			// Install Examples
			'sproutemail/settings/examples' =>
			'sproutemail/settings/_tabs/examples',

		));
	}

	public function init()
	{
		parent::init();

		Craft::import('plugins.sproutemail.enums.*');
		Craft::import('plugins.sproutemail.contracts.*');
		Craft::import('plugins.sproutemail.integrations.sproutemail.*');

		if (sproutEmailDefaultMailer()->enableDynamicLists())
		{
			craft()->on('sproutCommerce.saveProduct', array(sproutEmailDefaultMailer(), 'handleSaveProduct'));
			craft()->on('sproutCommerce.checkoutEnd', array(sproutEmailDefaultMailer(), 'handleCheckoutEnd'));
		}

		sproutEmail()->notifications->registerDynamicEventHandler();

		craft()->on('email.onBeforeSendEmail', array(sproutEmail(), 'handleOnBeforeSendEmail'));

		if (craft()->request->isCpRequest() && craft()->request->getSegment(1) == 'sproutemail')
		{
			// @todo Craft 3 - update to use info from config.json
			craft()->templates->includeJsResource('sproutemail/js/brand.js');
			craft()->templates->includeJs("
				sproutFormsBrand = new Craft.SproutBrand();
				sproutFormsBrand.displayFooter({
					pluginName: 'Sprout Email',
					pluginUrl: 'http://sprout.barrelstrengthdesign.com/craft-plugins/email',
					pluginVersion: '" . $this->getVersion() . "',
					pluginDescription: '" . $this->getDescription() . "',
					developerName: '(Barrel Strength)',
					developerUrl: '" . $this->getDeveloperUrl() . "'
				});
			");
		}

		// Logs sent element types for notifications
		craft()->on('sproutEmail_sentEmails.onSendNotification', function(Event $event) {

			$params = $event->params;

			$emailModel = $params['emailModel'];

			// To make sure you run the sprout notification email events only
			$sproutEmailEntry = isset($params['sproutEmailEntry']) ? $params['sproutEmailEntry'] : null;
			$mocked           = $params['mocked'];

			if($sproutEmailEntry != null)
			{

				$entryId = $sproutEmailEntry->id;
				$notificationRecord = SproutEmail_NotificationRecord::model()->findByAttributes(array('campaignId' => $sproutEmailEntry->campaignId));

				$notificationId = isset($notificationRecord) ? $notificationRecord->id : null;

				$type = ($mocked == true) ? 'Test Notification' : 'Notification';
				$info = array();
				$info['Sender'] = $sproutEmailEntry->fromEmail;
				$info['Type']   = $type;

				sproutEmail()->sentemails->logSentEmail($emailModel, $info);
			}
		});

		// This will trigger campaign emails
		craft()->on('sproutEmail_mailer.onSendCampaign', function(Event $event) {

			$entryModel = $event->params['entryModel'];
			$emailModel = $event->params['emailModel'];
			$campaign   = $event->params['campaign'];

			$entryId =  $entryModel->id;

			$info = array();
			$info['Sender'] = $entryModel->fromEmail;
			$mailer = $campaign->mailer;
			$info['Mailer'] = ucwords($mailer);
			$info['Type']   = "Campaign";

			sproutEmail()->sentemails->logSentEmail($emailModel, $info);
		});

		craft()->on('email.onSendEmail', function(Event $event) {

			$params = $event->params;
			$emailModel = $params['emailModel'];
			$variables  = $params['variables'];

			$sproutEmailEntry = isset($variables['sproutEmailEntry']) ? $variables['sproutEmailEntry'] : null;

			if($sproutEmailEntry == null)
			{
				$info = array();

				$emailKey = isset($variables['emailKey']) ? $variables['emailKey'] : null;

				if($emailKey == 'test_email')
				{
					$emailModel->toEmail = $variables['settings']['emailAddress'];
				}

				if($emailKey != null)
				{
					$type = ucwords(str_replace('_', ' ', $emailKey));
					$info['Type'] = $type;
				}

				$info['Sender'] = $emailModel->fromEmail;

				sproutEmail()->sentemails->logSentEmail($emailModel, $info);
			}
		});
	}

	/**
	 * @throws \Exception
	 * @return SproutEmailTwigExtension
	 */
	public function addTwigExtension()
	{
		Craft::import('plugins.sproutemail.twigextensions.SproutEmailTwigExtension');

		return new SproutEmailTwigExtension();
	}

	/**
	 * Using our own API to register native Craft events
	 *
	 * @return array
	 */
	public function defineSproutEmailEvents()
	{

		if ($this->isEnabled && $this->isInstalled)
		{
			$events = array(
				'entries.saveEntry'   => new SproutEmail_EntriesSaveEntryEvent(),
				'entries.deleteEntry' => new SproutEmail_EntriesDeleteEntryEvent(),
				'userSession.login'   => new SproutEmail_UserSessionLoginEvent(),
				'users.saveUser'      => new SproutEmail_UsersSaveUserEvent(),
				'users.deleteUser'    => new SproutEmail_UsersDeleteUserEvent(),
				'users.activateUser'  => new SproutEmail_UsersActivateUserEvent(),
			);
		}

		// Check if craft commerce plugin is installed and enabled
		$commercePlugin = craft()->plugins->getPlugin('commerce', false);

		// Commerce events goes here
		if(isset($commercePlugin->isEnabled) && $commercePlugin->isEnabled)
		{
			$events['commerce_orders.onOrderComplete']         = new SproutEmail_CommerceOnOrderCompleteEvent();
			$events['commerce_transactions.onSaveTransaction'] = new SproutEmail_CommerceOnSaveTransactionEvent();
			$events['commerce_orderHistories.onStatusChange']  = new SproutEmail_CommerceOnStatusChangeEvent();
		}

		return $events;
	}

	/**
	 * Using our own API to register native Craft events
	 *
	 * @return array
	 */
	public function defineSproutEmailMailers()
	{
		require_once dirname(__FILE__).'/integrations/sproutemail/mailers/SproutEmailDefaultMailer.php';

		return array(
			'defaultmailer' => new SproutEmailDefaultMailer()
		);
	}

	/**
	 * @throws \Exception
	 */
	public function onBeforeInstall()
	{
		Craft::import('plugins.sproutemail.enums.Campaign');
	}

	/**
	 * Installs all available mailers if any
	 */
	public function onAfterInstall()
	{
		try
		{
			if (!$this->getIsInitialized())
			{
				$this->init();
			}

			sproutEmail()->mailers->installMailers();

			// Redirect to examples after installation
			craft()->request->redirect(UrlHelper::getCpUrl().'/sproutemail/settings/examples');
		}
		catch (\Exception $e)
		{
			sproutEmail()->error($e->getMessage());
		}
	}

	/**
	 * @return array
	 */
	public function sproutMigrateRegisterElements()
	{
		return array(
				'sproutemail_entry'     => array(
						'model'   => 'Craft\\SproutEmail_Entry',
						'method'  => 'saveEntry',
						'service' => 'sproutEmail_entry',
				)
		);
	}
}

/**
 * @return SproutEmailService
 */
function sproutEmail()
{
	return Craft::app()->getComponent('sproutEmail');
}

/**
 * @return SproutEmail_DefaultMailerService
 */
function sproutEmailDefaultMailer()
{
	return Craft::app()->getComponent('sproutEmail_defaultMailer');
}
