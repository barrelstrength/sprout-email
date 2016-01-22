<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m160122_065238_sproutEmail_addSentEmail extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// The Table you wish to add. 'craft_' prefix will be added automatically.
		$tableName = 'sproutemail_sentemail';

		if (!craft()->db->tableExists($tableName))
		{
			Craft::log('Creating the `$tableName` table.', LogLevel::Info, true);

			// Create the craft_sproutemail_sentemail table
			craft()->db->createCommand()->createTable($tableName, array(
				'id'                 		=> array('column' => 'integer', 'required' => true, 'primaryKey' => true),
				'campaignEntryId'    		=> array('column' => 'integer', 'required' => true),
				'campaignNotificationId'    => array('column' => 'integer'),
				'emailSubject' 				=> array('column' => 'text'),
				'title' 					=> array('column' => 'text'),
				'fromEmail' 				=> array(
													'maxLength' => 255,
													'column' => 'varchar'
												),
				'fromName' 					=> array(
													'maxLength' => 255,
													'column' => 'varchar'
												),
				'toEmail' 					=> array(
													'maxLength' => 255,
													'column' => 'varchar'
												),
				'body' 						=> array('column' => 'text'),
				'htmlBody' 					=> array('column' => 'text'),
				'sender' 					=> array('column' => 'text'),

			), null, false);

			// Add foreign keys to craft_sproutemail_campaigns_entries
			craft()->db->createCommand()->addForeignKey($tableName, 'id', 'elements', 'id', 'CASCADE', null);
			craft()->db->createCommand()->addForeignKey($tableName, 'campaignEntryId', 'sproutemail_campaigns_entries', 'id', 'CASCADE', null);
		}

		return true;
	}
}
