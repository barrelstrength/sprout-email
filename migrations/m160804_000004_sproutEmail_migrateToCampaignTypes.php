<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m160804_000004_sproutEmail_migrateToCampaignTypes extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		SproutEmailPlugin::log('Updating to campaign types');

		$tableName  = "sproutemail_campaigns";

		if (craft()->db->tableExists($tableName))
		{
			if (craft()->db->columnExists($tableName, 'type'))
			{
				SproutEmailPlugin::log('Remove `type` column from sproutemail_campaigns table');

				craft()->db->createCommand()->dropColumn($tableName, 'type');
			}

			if (craft()->db->columnExists($tableName, 'notifications'))
			{
				SproutEmailPlugin::log('Remove `notifications` column from sproutemail_campaigns table');

				craft()->db->createCommand()->dropColumn($tableName, 'notifications');
			}

			if (!craft()->db->tableExists('sproutemail_campaigntype'))
			{
				SproutEmailPlugin::log('Rename sproutemail_campaigns table to sproutemail_campaigntype');

				// Solve issue on older version of MySQL where we can't rename columns with a FK
				MigrationHelper::dropForeignKeyIfExists($tableName, array('fieldLayoutId'));

				craft()->db->createCommand()->renameTable($tableName, 'sproutemail_campaigntype');

				$table = MigrationHelper::getTable($tableName);

				// Drop the FK again, since renameTable restores fks
				MigrationHelper::dropAllForeignKeysOnTable($table);

				craft()->db->createCommand()->addForeignKey($tableName, 'fieldLayoutId', 'fieldlayouts', 'id', 'SET NULL');
			}
		}

		SproutEmailPlugin::log('Finished updating to campaign types');

		return true;
	}
}
