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
		return '2.4.7';
	}

	/**
	 * @return string
	 */
	public function getSchemaVersion()
	{
		return '2.4.0';
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
			'pluginNameOverride'       => AttributeType::String,
			'enableCampaignEmails'     => array(AttributeType::Bool, 'default' => true),
			'enableNotificationEmails' => array(AttributeType::Bool, 'default' => true),
			'enableSentEmails'         => array(AttributeType::Bool, 'default' => false),
			'enableRecipientLists'     => array(AttributeType::Bool, 'default' => false)
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
		return array(
			// Campaign Email Edit Page
			'sproutemail/campaigns/(?P<campaignTypeId>\d+)/new'                                          => array(
				'action' => 'sproutEmail/campaignEmails/editCampaignEmailTemplate'
			),
			'sproutemail/campaigns/edit/(?P<emailId>\d+)'                                                => array(
				'action' => 'sproutEmail/campaignEmails/editCampaignEmailTemplate'
			),

			// Notification Email Edit Page
			'sproutemail/notifications/edit/(?P<notificationId>\d+)'                                     => array(
				'action' => 'sproutEmail/notificationEmails/editNotificationEmailTemplate'
			),

			// Notification Email Settings
			'sproutemail/settings/(?P<settingsTemplate>notifications)/edit/(?P<emailId>\d+|new)'         => array(
				'action' => 'sproutEmail/notificationEmails/editNotificationEmailSettingsTemplate'
			),

			// Campaign Type Settings
			'sproutemail/settings/(?P<settingsTemplate>campaigntypes)/edit/(?P<campaignTypeId>\d+|new)?' => array(
				'action' => 'sproutEmail/campaignType/campaignSettingsTemplate'
			),

			// Mailer Settings Route
			'sproutemail/settings/(?P<settingsTemplate>mailers)/(?P<mailerId>[a-z]+)'                    => array(
				'action' => 'sproutEmail/mailer/editSettingsTemplate'
			),

			// Redirects to `general` settings
			'sproutemail/settings'                                                                       => array(
				'action' => 'sproutEmail/settingsIndexTemplate'
			),

			// Settings templates such as `general` and `mailers`
			'sproutemail/settings/(?P<settingsTemplate>.*)'                                              => array(
				'action' => 'sproutEmail/settingsIndexTemplate'
			),

			// Examples
			'sproutemail/settings/examples'                                                              =>
				'sproutemail/settings/_tabs/examples',
		);
	}

	public function init()
	{
		parent::init();

		// Sprout Email Contracts
		Craft::import('plugins.sproutemail.contracts.SproutEmailBaseEvent');
		Craft::import('plugins.sproutemail.contracts.SproutEmailBaseMailer');
		Craft::import('plugins.sproutemail.contracts.SproutEmailCampaignEmailSenderInterface');
		Craft::import('plugins.sproutemail.contracts.SproutEmailNotificationEmailSenderInterface');

		// Sprout Email Mailers
		Craft::import('plugins.sproutemail.integrations.sproutemail.mailers.SproutEmail_CopyPasteMailer');
		Craft::import('plugins.sproutemail.integrations.sproutemail.mailers.SproutEmail_DefaultMailer');

		// Sprout Email Events
		Craft::import('plugins.sproutemail.integrations.sproutemail.SproutEmail_CommerceOnOrderCompleteEvent');
		Craft::import('plugins.sproutemail.integrations.sproutemail.SproutEmail_CommerceOnSaveTransactionEvent');
		Craft::import('plugins.sproutemail.integrations.sproutemail.SproutEmail_CommerceOnStatusChangeEvent');
		Craft::import('plugins.sproutemail.integrations.sproutemail.SproutEmail_EntriesDeleteEntryEvent');
		Craft::import('plugins.sproutemail.integrations.sproutemail.SproutEmail_EntriesSaveEntryEvent');
		Craft::import('plugins.sproutemail.integrations.sproutemail.SproutEmail_UsersActivateUserEvent');
		Craft::import('plugins.sproutemail.integrations.sproutemail.SproutEmail_UsersDeleteUserEvent');
		Craft::import('plugins.sproutemail.integrations.sproutemail.SproutEmail_UserSessionLoginEvent');
		Craft::import('plugins.sproutemail.integrations.sproutemail.SproutEmail_UsersSaveUserEvent');

		// Sprout Import Importers
		Craft::import('plugins.sproutemail.integrations.sproutimport.SproutEmail_CampaignEmailSproutImportElementImporter');
		Craft::import('plugins.sproutemail.integrations.sproutimport.SproutEmail_CampaignTypeSproutImportSettingsImporter');
		Craft::import('plugins.sproutemail.integrations.sproutimport.SproutEmail_NotificationEmailSproutImportElementImporter');

		if ($this->getSettings()->enableNotificationEmails)
		{
			sproutEmail()->notificationEmails->registerDynamicEventHandler();
		}

		craft()->on('email.onBeforeSendEmail', array(sproutEmail(), 'handleOnBeforeSendEmail'));

		if (sproutEmail()->defaultmailer->enableDynamicLists())
		{
			craft()->on('sproutCommerce.saveProduct', array(sproutEmailDefaultMailer(), 'handleSaveProduct'));
			craft()->on('sproutCommerce.checkoutEnd', array(sproutEmailDefaultMailer(), 'handleCheckoutEnd'));
		}

		craft()->on('sproutEmail.onSendSproutEmail', function (Event $event)
		{
			sproutEmail()->sentEmails->logSentEmailCampaign($event);
		});

		craft()->on('email.onSendEmail', function (Event $event)
		{
			sproutEmail()->sentEmails->logSentEmail($event);
		});

		craft()->on('sproutEmail.onSendEmailError', function (Event $event)
		{
			sproutEmail()->handleLogSentEmailOnSendEmailError($event);
		});

		craft()->on('email.onSendEmailError', function (Event $event)
		{
			// Add failed status.
			$event->params['deliveryStatus'] = 'failed';

			sproutEmail()->handleLogSentEmailOnSendEmailError($event);
		});

		if (craft()->request->isCpRequest() && craft()->request->getSegment(1) == 'sproutemail')
		{
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
				'users.activateUser'  => new SproutEmail_UsersActivateUserEvent()
			);
		}

		// Make sure Craft Commerce is installed and enabled before we register any Craft Commerce events
		$commercePlugin = craft()->plugins->getPlugin('commerce');

		if (isset($commercePlugin))
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
		$mailers = array();

		Craft::import('plugins.sproutemail.integrations.sproutemail.mailers.*');
		$mailers['defaultmailer'] = new SproutEmail_DefaultMailer();

		$pluginMailers = array(
			'copypaste'       => 'SproutEmail_CopyPasteMailer',
		);

		foreach ($pluginMailers as $handle => $class)
		{
			$namespace   = "Craft\\" . $class;
			$mailerClass = new $namespace();

			$mailers[$handle] = $mailerClass;
		}

		return $mailers;
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
			craft()->request->redirect(UrlHelper::getCpUrl() . '/sproutemail/settings/examples');
		}
		catch (\Exception $e)
		{
			sproutEmail()->error($e->getMessage());
		}
	}

	/**
	 * @return array
	 */
	public function registerSproutSeoSitemap()
	{
		return array(
			'sproutemail_campaignemails' => array(
				'name'                   => 'Email Campaigns',
				'elementType'            => 'SproutEmail_CampaignEmail',
				'elementGroupId'         => 'campaignTypeId',
				'service'                => 'sproutEmail_campaignTypes',
				'method'                 => 'getCampaignTypes',
				'matchedElementVariable' => 'email'
			)
		);
	}

	public function registerSproutImportImporters()
	{
		return array(
			new SproutEmail_CampaignEmailSproutImportElementImporter(),
			new SproutEmail_NotificationEmailSproutImportElementImporter(),
			new SproutEmail_CampaignTypeSproutImportSettingsImporter()
		);
	}

	/**
	 * Override SproutEmailPlugin::log() method to allow the logging of
	 * multiple messages and arrays
	 *
	 * Examples:
	 *
	 * Standard log:
	 * SproutEmailPlugin::log($msg);
	 *
	 * Enhanced log:
	 * $messages['thing1'] = Craft::t('Something happened');
	 * $messages['thing2'] = $entry->getErrors();
	 * SproutEmailPlugin::log($messages);
	 *
	 * @param string $messages
	 * @param string $level
	 * @param bool   $force
	 *
	 * @return null - writes log to logfile
	 */
	public static function log($messages, $level = LogLevel::Info, $force = false)
	{
		$msg = "";

		if (is_array($messages))
		{
			foreach ($messages as $message)
			{
				$msg .= PHP_EOL . print_r($message, true);
			}
		}
		else
		{
			$msg = $messages;
		}

		parent::log($msg, $level, $force);
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
