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
				'id'           => array(
					'column' => 'integer',
					'required' => true
				),
				'title'        => array(
					'required' => false,
					'column'   => 'text'
				),
				'emailSubject' => array(
					'required' => false,
					'column'   => 'text'
				),
				'fromEmail'    => array(
					'required' => false,
					'column'   => 'text'
				),
				'fromName'     => array(
					'required' => false,
					'column'   => 'text'
				),
				'toEmail'      => array(
					'required' => false,
					'column'   => 'text'
				),
				'body'         => array(
					'required' => false,
					'column'   => 'text'
				),
				'htmlBody'     => array(
					'required' => false,
					'column'   => 'text'
				),
				'info'         => array(
					'required' => false,
					'column'   => 'text'
				),

			), null, false);

			craft()->db->createCommand()->addPrimaryKey($tableName, 'id');
			craft()->db->createCommand()->createIndex($tableName, 'id');
			craft()->db->createCommand()->addForeignKey($tableName, 'id', 'elements', 'id', 'CASCADE');
		}

		return true;
	}
}
