<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m160804_000005_sproutEmail_updateCampaignTypeForeignKeys extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$tableName = 'sproutemail_campaigntype';

		if (craft()->db->tableExists($tableName))
		{
			$table = MigrationHelper::getTable($tableName);

			// Drop the FK again, since renameTable restores fks
			MigrationHelper::dropAllForeignKeysOnTable($table);

			craft()->db->createCommand()->addForeignKey($tableName, 'fieldLayoutId', 'fieldlayouts', 'id', 'SET NULL');
		}

		return true;
	}
}
