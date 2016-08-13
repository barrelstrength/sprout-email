<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m160804_000005_sproutEmail_migrateCampaignEmails extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$tableName = "sproutemail_campaigns_entries";

		if (craft()->db->tableExists($tableName) && !craft()->db->tableExists('sproutemail_campaigns'))
		{
			SproutEmailPlugin::log('Rename sproutemail_campaigns_entries table to sproutemail_campaigns');

			craft()->db->createCommand()->renameTable($tableName, 'sproutemail_campaigns');
		}

		return true;
	}
}
