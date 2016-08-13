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
		$oldTableName = "sproutemail_campaigns_entries";
		$newTableName = "sproutemail_campaigns";

		if (craft()->db->tableExists($oldTableName) && !craft()->db->tableExists($newTableName))
		{
			SproutEmailPlugin::log('Rename sproutemail_campaigns_entries table to sproutemail_campaigns');

			$oldTable = MigrationHelper::getTable($oldTableName);

			// Solve issue on older version of MySQL where we can't rename columns with a FK
			MigrationHelper::dropAllForeignKeysOnTable($oldTable);

			craft()->db->createCommand()->renameTable($oldTableName, $newTableName);

			$newTable = MigrationHelper::getTable($newTableName);

			// Drop the FK again, since renameTable restores fks
			MigrationHelper::dropAllForeignKeysOnTable($newTable);

			craft()->db->createCommand()->addForeignKey($newTableName, 'id', 'elements', 'id', 'CASCADE');
			craft()->db->createCommand()->addForeignKey($newTableName, 'campaignId', 'sproutemail_campaigntype', 'id', 'CASCADE');
		}

		return true;
	}
}
