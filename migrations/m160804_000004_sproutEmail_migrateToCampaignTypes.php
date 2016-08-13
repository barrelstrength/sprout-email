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
				SproutEmailPlugin::log('Remove type column from sproutemail_campaigns table');

				craft()->db->createCommand()->dropColumn($tableName, 'type');
			}

			if (!craft()->db->tableExists('sproutemail_campaigntype'))
			{
				SproutEmailPlugin::log('Rename sproutemail_campaigns table to sproutemail_campaigntype');

				craft()->db->createCommand()->renameTable($tableName, 'sproutemail_campaigntype');
			}
		}

		SproutEmailPlugin::log('Finished updating to campaign types');

		return true;
	}
}
