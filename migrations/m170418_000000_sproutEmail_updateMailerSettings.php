<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m170418_000000_sproutEmail_updateMailerSettings extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$this->updateCampaignMonitorMailerSettings();
		$this->updateMailChimpMailerSettings();
	}

	public function updateCampaignMonitorMailerSettings()
	{
		$plugin = craft()->plugins->getPlugin('sproutCampaignMonitor', false);

		if ($plugin->isInstalled)
		{
			$settings = craft()->db->createCommand()
				->select('settings')
				->from('sproutemail_mailers')
				->where(array('name' => 'campaignmonitor'))
				->queryScalar();

			craft()->db->createCommand()->update('plugins', array(
				'settings' => $settings
			),
				'class= :class', array(':class' => 'SproutCampaignMonitor')
			);
		}
	}

	public function updateMailChimpMailerSettings()
	{
		$plugin = craft()->plugins->getPlugin('sproutMailChimp', false);

		if ($plugin->isInstalled)
		{
			$settings = craft()->db->createCommand()
				->select('settings')
				->from('sproutemail_mailers')
				->where(array('name' => 'mailchimp'))
				->queryScalar();

			craft()->db->createCommand()->update('plugins', array(
				'settings' => $settings
			),
				'class= :class', array(':class' => 'SproutMailChimp')
			);
		}
	}
}
