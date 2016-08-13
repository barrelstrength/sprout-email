<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m160804_000008_sproutEmail_removeOldTables extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		if (craft()->db->tableExists('sproutemail_campaigns_notifications'))
		{
			SproutEmailPlugin::log('Remove sproutemail_campaigns_notifications table');

			craft()->db->createCommand()->dropTable('sproutemail_campaigns_notifications');
		}

		return true;
	}
}
