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
		return '2.2.4';
	}

	/**
	 * @return string
	 */
	public function getSchemaVersion()
	{
		return '2.2.2';
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
		// @formatter:off

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

		// @formatter:on
	}

	public function init()
	{
		parent::init();

		Craft::import('plugins.sproutemail.enums.*');
		Craft::import('plugins.sproutemail.contracts.*');
		Craft::import('plugins.sproutemail.integrations.sproutemail.*');
		Craft::import('plugins.sproutemail.integrations.sproutimport.*');

		sproutEmail()->notifications->registerDynamicEventHandler();

		craft()->on('email.onBeforeSendEmail', array(sproutEmail(), 'handleOnBeforeSendEmail'));

		if (sproutEmail()->defaultmailer->enableDynamicLists())
		{
			craft()->on('sproutCommerce.saveProduct', array(sproutEmailDefaultMailer(), 'handleSaveProduct'));
			craft()->on('sproutCommerce.checkoutEnd', array(sproutEmailDefaultMailer(), 'handleCheckoutEnd'));
		}

		craft()->on('sproutEmail.onSendCampaign', function (Event $event)
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
			sproutEmail()->handleLogSentEmailOnSendEmailError($event);
		});

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
			'mailchimp'       => 'SproutEmail_MailchimpMailer',
			'copypaste'       => 'SproutEmail_CopyPasteMailer',
			'campaignmonitor' => 'SproutEmail_CampaignMonitorMailer'
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
			'sproutemail_entry'         => array(
				'name'           => 'Email Campaigns',
				'elementType'    => 'SproutEmail_Entry',
				'elementGroupId' => 'campaignId',
				'service'        => 'sproutEmail_campaigns',
				'method'         => 'getCampaigns'
			)
		);
	}

	public function registerSproutImportImporters()
	{
		return array(
			new SproutEmail_EntrySproutImportElementImporter
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
