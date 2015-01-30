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
		return '0.8.4';
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

		Craft::import('plugins.sproutemail.enums.*');
		Craft::import('plugins.sproutemail.contracts.*');
		Craft::import('plugins.sproutemail.integrations.sproutemail.*');

		sproutEmail()->notifications->registerDynamicEventHandler();
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
