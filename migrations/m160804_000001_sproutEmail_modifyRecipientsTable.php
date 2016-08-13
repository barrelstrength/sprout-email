<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m160804_000001_sproutEmail_modifyRecipientsTable extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		SproutEmailPlugin::log('Modifying the sproutemail_campaigns_entries_recipientlists table');

		$tableName = 'sproutemail_campaigns_entries_recipientlists';

		if (craft()->db->tableExists($tableName))
		{
			if (craft()->db->columnExists($tableName, 'entryId'))
			{
				SproutEmailPlugin::log('Updating Foreign Key on sproutemail_campaigns_entries_recipientlists table from entryId => emailId');

				// Solve issue on older version of MySQL where we can't rename columns with a FK
				MigrationHelper::dropForeignKeyIfExists($tableName, array('entryId'));

				//craft()->db->createCommand()->addForeignKey($tableName, 'entryId', 'elements', 'id', 'CASCADE');

				MigrationHelper::renameColumn($tableName, 'entryId', 'emailId');
			}

			if (craft()->db->columnExists($tableName, 'type'))
			{
				SproutEmailPlugin::log('Removing type column from sproutemail_campaigns_entries_recipientlists table');

				craft()->db->createCommand()->dropColumn($tableName, 'type');
			}
		}

		SproutEmailPlugin::log('Finished modifying the sproutemail_campaigns_entries_recipientlists table');

		return true;
	}
}
