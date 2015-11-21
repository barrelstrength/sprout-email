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
	public function getVersion()
	{
		return '1.2.3';
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
	 * @return bool
	 */
	public function hasCpSection()
	{
		return (craft()->userSession->isAdmin() or craft()->userSession->user->can('manageEmail'));
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
	 * @return array
	 */
	public function registerUserPermissions()
	{
		return array(
			'manageEmail' => array(
				'label' => Craft::t('Manage Email Section')
			),
			'editSproutEmailSettings' => array(
				'label' => Craft::t('Edit Email Settings')
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
			'sproutemail/settings/mailers/(?P<mailerId>[a-z]+)' => array(
				'action' => 'sproutEmail/mailer/editSettings'
			),
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
			'sproutemail/events/new' => 'sproutemail/events/_edit',
			'sproutemail/events/edit/(?P<eventId>\d+)' => 'sproutemail/events/_edit',

			// Install Examples
			'sproutemail/examples' =>
			'sproutemail/_cp/examples',
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
			return array(
				'entries.saveEntry'   => new SproutEmail_EntriesSaveEntryEvent(),
				'entries.deleteEntry' => new SproutEmail_EntriesDeleteEntryEvent(),
				'userSession.login'   => new SproutEmail_UserSessionLoginEvent(),
				'users.saveUser'      => new SproutEmail_UsersSaveUserEvent(),
				'users.deleteUser'    => new SproutEmail_UsersDeleteUserEvent(),
				'users.activateUser'  => new SproutEmail_UsersActivateUserEvent(),
			);
		}
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
			craft()->request->redirect(UrlHelper::getCpUrl().'/sproutemail/examples');
		}
		catch (\Exception $e)
		{
			sproutEmail()->error($e->getMessage());
		}
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
