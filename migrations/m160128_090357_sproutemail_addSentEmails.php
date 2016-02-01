<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m160128_090357_sproutEmail_addSentEmails extends BaseMigration
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
				'campaignNotificationId'    => array('column' => 'integer', 'required' => false),
				'emailSubject' 			=> array(
					'required' => false,
					'column' => 'text'
				),
				'title' 					=> array(
					'required' => false,
					'column' => 'text'
				),
				'fromEmail' 				=>  array(
					'required' => false,
					'column'   => 'text'
				),
				'fromName' 					=> array(
					'required' => false,
					'column'   => 'text'
				),
				'toEmail' 					=> array(
					'required' => false,
					'column'   => 'text'
				),
				'body' 						=> array(
					'required' => false,
					'column'   => 'text'
				),
				'htmlBody' 					=> array(
					'required' => false,
					'column'   => 'text'
				),
				'type' 					    => array(
					'required' => false,
					'column'   => 'text'
				)

			), null, false);

			// Add foreign keys to craft_sproutemail_campaigns_entries
			craft()->db->createCommand()->addForeignKey($tableName, 'id', 'elements', 'id', 'CASCADE', null);
			craft()->db->createCommand()->addForeignKey($tableName, 'campaignEntryId', 'sproutemail_campaigns_entries', 'id', 'CASCADE', null);
			craft()->db->createCommand()->addForeignKey($tableName, 'campaignNotificationId', 'sproutemail_campaigns_notifications', 'id', 'SET NULL', null);


		}

		return true;
	}
}
