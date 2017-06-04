<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m170603_000001_sproutEmail_removeSentColumnFromCampaignAndNotificationTables extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		if ($this->dropColumn('sproutemail_campaignemails', 'sent'))
		{
			Craft::log('The `sent` column has been removed from the sproutemail_campaignemails table.',
				LogLevel::Info);
		}
		else
		{
			Craft::log('The `sent` column could not be removed from the sproutemail_campaignemails table.',
				LogLevel::Error);
		}

		if ($this->dropColumn('sproutemail_notificationemails', 'sent'))
		{
			Craft::log('The `sent` column has been removed from the sproutemail_notificationemails table.',
				LogLevel::Info);
		}
		else
		{
			Craft::log('The `sent` column could not be removed from the sproutemail_notificationemails table.',
				LogLevel::Error);
		}

		return true;
	}
}
