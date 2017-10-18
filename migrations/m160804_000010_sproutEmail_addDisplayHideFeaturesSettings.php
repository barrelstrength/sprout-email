<?php

namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m160804_000010_sproutEmail_addDisplayHideFeaturesSettings extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		SproutEmailPlugin::log('Updating general settings to display or hide Sent Emails and Recipients');

		$plugin   = craft()->plugins->getPlugin('sproutemail');
		$settings = $plugin->getSettings();

		// Update Sent Emails to be enabled to keep tracking
		$settings->enableSentEmails = '1';

		// Update Recipients to be enabled if any Recipient Lists exist
		if (craft()->db->tableExists('sproutemail_defaultmailer_recipientlists'))
		{
			$lists = craft()->db->createCommand()
				->select('*')
				->from('sproutemail_defaultmailer_recipientlists')
				->queryAll();

			if (count($lists))
			{
				$settings->enableRecipientLists = '1';
			}
		}

		craft()->plugins->savePluginSettings($plugin, $settings);

		SproutEmailPlugin::log('Finished updating general settings to display or hide Sent Emails and Recipients');

		return true;
	}
}
