<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m160804_081709_sproutemail_NotificationEmail extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$tableName  = "sproutemail_campaigns";
		$columnName = "type";

		if (craft()->db->tableExists($tableName))
		{
			if (craft()->db->columnExists($tableName, 'type'))
			{
				craft()->db->createCommand()->dropColumn($tableName, 'type');
			}

			if (!craft()->db->tableExists('sproutemail_campaigntype'))
			{
				craft()->db->createCommand()->renameTable($tableName, 'sproutemail_campaigntype');
			}
		}

		$tableName  = "sproutemail_campaigns_entries";

		if (craft()->db->tableExists($tableName))
		{
			craft()->db->createCommand()->renameTable($tableName, 'sproutemail_campaigns');
		}

		$tableName = 'sproutemail_notifications';

		if (!craft()->db->tableExists($tableName))
		{
			// Create the craft_sproutemail_notifications table
			craft()->db->createCommand()->createTable($tableName, array(
				'id'                    => array('column' => 'integer', 'required' => true, 'primaryKey' => true),
				'name'                  => array('required' => true),
				'template'              => array('required' => true),
				'eventId'               => array('column' => 'text'),
				'options'               => array('column' => 'text'),
				'subjectLine'           => array('required' => true),
				'recipients'            => array('required' => false),
				'fromName'              => array('required' => false),
				'fromEmail'             => array('required' => false),
				'replyToEmail'          => array('required' => false),
				'sent'                  => array('maxLength' => 1,
				                                 'default' => false,
				                                 'required' => true,
				                                 'column' => 'tinyint',
				                                 'unsigned' => true),
				'enableFileAttachments' => array('maxLength' => 1,
				                                 'default' => false,
				                                 'required' => true,
				                                 'column' => 'tinyint',
				                                 'unsigned' => true),
				'dateCreated'           => array('column' => 'datetime'),
				'dateUpdated'           => array('column' => 'datetime'),
				'fieldLayoutId'         => array('maxLength' => 11,
				                                 'decimals' => 0,
				                                 'unsigned' => false,
				                                 'length' => 11,
				                                 'column' => 'integer'),
			), null, false);

			craft()->db->createCommand()->addPrimaryKey($tableName, 'id');
			craft()->db->createCommand()->createIndex($tableName, 'id');

			// Add foreign keys to craft_sproutemail_notifications
			craft()->db->createCommand()->addForeignKey($tableName, 'id', 'elements', 'id', 'CASCADE', null);
			craft()->db->createCommand()->addForeignKey($tableName, 'fieldLayoutId', 'fieldlayouts',
				'id', 'SET NULL', null);
		}
		$tableName = 'sproutemail_notification_recipientlists';

		if (!craft()->db->tableExists($tableName))
		{
			// Create the craft_sproutemail_notification_recipientlists table
			craft()->db->createCommand()->createTable($tableName, array(
				'notificationId' => array('column' => 'integer', 'required' => true),
				'list'           => array(),
			), null, true);

			// Add foreign keys to craft_sproutemail_notification_recipientlists
			craft()->db->createCommand()->addForeignKey($tableName, 'notificationId', 'sproutemail_notifications', 'id', 'CASCADE', null);
		}

		SproutEmailPlugin::log('!Running naming convention migration');

		return true;
	}
}
