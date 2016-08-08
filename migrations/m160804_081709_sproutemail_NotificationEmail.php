<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m160804_081709_sproutemail_NotificationEmail extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$tableName  = "sproutemail_campaigns";
		$columnName = "type";

		if (craft()->db->tableExists($tableName))
		{
			craft()->db->createCommand()->dropColumn($tableName, 'type');

			craft()->db->createCommand()->renameTable($tableName, 'sproutemail_campaigntype');
		}

		$tableName  = "sproutemail_campaigns_entries";

		if (craft()->db->tableExists($tableName))
		{
			craft()->db->createCommand()->renameTable($tableName, 'sproutemail_campaigns');
		}

		SproutEmailPlugin::log('Running naming convention migration');

		return true;
	}
}
