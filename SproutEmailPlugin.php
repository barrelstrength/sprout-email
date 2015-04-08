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
		return '0.8.8';
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
		return array(
			'pluginNameOverride' => AttributeType::String
		);
	}

	public function registerUserPermissions()
	{
		return array(
			'manageEmail'             => array(
				'label' => Craft::t('Manage Email Section')
			),
			'editSproutFormsSettings' => array(
				'label' => Craft::t('Edit Form Settings')
			)
		);
	}

	public function registerCpRoutes()
	{
		$url            = 'sproutemail/defaultmailer';
		$ctrl           = 'sproutEmail/defaultMailer';
		$recipients     = $url.'/recipients';
		$recipientLists = $url.'/recipientlists';
		$defaultMailer  = array(
			$url => array(
				'action' => 'templates/render',
				'params' => array('template' => 'sproutemail/defaultmailer/_index')
			),
			$recipients                           => array('action' => $ctrl.'/showIndexRecipientTemplate'),
			$recipients.'/new'                    => array('action' => $ctrl.'/showEditRecipientTemplate'),
			$recipients.'/edit/(?P<id>[\d]+)'     => array('action' => $ctrl.'/showEditRecipientTemplate'),
			# ~
			$recipientLists                       => array('action' => $ctrl.'/showIndexRecipientListTemplate'),
			$recipientLists.'/new'                => array('action' => $ctrl.'/showEditRecipientListTemplate'),
			$recipientLists.'/edit/(?P<id>[\d]+)' => array('action' => $ctrl.'/showEditRecipientListTemplate'),
		);

		return array_merge($defaultMailer, array(
			'sproutemail/settings/mailers/(?P<mailerId>[a-z]+)' => array(
				'action' => 'sproutEmail/mailer/editSettings'
			),
			'sproutemail/settings/campaigns/edit/(?P<campaignId>\d+|new)(/(template|recipients|fields))?'  => array(
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
		));
	}

	public function init()
	{
		parent::init();

		Craft::import('plugins.sproutemail.enums.*');
		Craft::import('plugins.sproutemail.contracts.*');
		Craft::import('plugins.sproutemail.integrations.sproutemail.*');

		craft()->on('sproutCommerce.saveProduct', array(sproutEmailDefaultMailer(), 'handleSaveProduct'));
		craft()->on('sproutCommerce.checkoutEnd', array(sproutEmailDefaultMailer(), 'handleCheckoutEnd'));

		sproutEmail()->notifications->registerDynamicEventHandler();
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
				'entries.saveEntry' => new SproutEmail_EntriesSaveEntryEvent(),
				'userSession.login' => new SproutEmail_UserSessionLoginEvent(),
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
		}
		catch (\Exception $e)
		{
			sproutEmail()->error($e->getMessage());
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
