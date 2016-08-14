<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m160804_000007_sproutEmail_updateCampaignEmailForeignKeys extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		MigrationHelper::refresh();

		$tableName = 'sproutemail_campaignemails';

		if (craft()->db->tableExists($tableName))
		{
			$table = MigrationHelper::getTable($tableName);

			// Drop the FK again, since renameTable restores fks
			MigrationHelper::dropAllForeignKeysOnTable($table);

			craft()->db->createCommand()->addForeignKey($tableName, 'id', 'elements', 'id', 'CASCADE');
			craft()->db->createCommand()->addForeignKey($tableName, 'campaignId', 'sproutemail_campaigntype', 'id', 'CASCADE');
		}

		return true;
	}
}
