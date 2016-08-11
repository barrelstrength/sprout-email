<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m160804_081709_sproutEmail_NotificationEmail extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$this->createNotificationTables();

		$this->moveOldNotifications();

		$this->alterTablesColumns();

		$this->dropTables();

		SproutEmailPlugin::log('Running naming convention migration');

		return true;
	}

	private function createNotificationTables()
	{
		$tableName = 'sproutemail_notifications';

		if (!craft()->db->tableExists($tableName))
		{
			try
			{
				// Create the craft_sproutemail_notifications table
				craft()->db->createCommand()->createTable($tableName, array(
					'id'                    => array('column' => 'integer', 'required' => true),
					'name'                  => array('required' => true),
					'template'              => array('required' => true),
					'eventId'               => AttributeType::String,
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
					'fieldLayoutId'         => array('column' => 'integer',
					                                 'length' => 10),
				), null, false);

				craft()->db->createCommand()->addPrimaryKey($tableName, 'id');

				// Add foreign keys to craft_sproutemail_notifications
				craft()->db->createCommand()->addForeignKey($tableName, 'id', 'elements', 'id', 'CASCADE', null);
				craft()->db->createCommand()->addForeignKey($tableName, 'fieldLayoutId', 'fieldlayouts',
					'id', 'SET NULL', null);
			}
			catch (\Exception $e)
			{
				Craft::dd($e->getMessage(), 10, false);
			}
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
	}

	private function moveOldNotifications()
	{
		$entries = craft()->db->createCommand()
			->select('*')
			->from('sproutemail_campaigns setting')
			->join('sproutemail_campaigns_notifications notification', 'setting.id = notification.campaignId')
			->join('sproutemail_campaigns_entries email', 'setting.id = email.campaignId')
			->where(array('type' => 'notification'))
			->queryAll();

		$oldNotifications = array();
		foreach ($entries as $key => $entry)
		{
			$recipientList = craft()->db->createCommand()
				->select('*')
				->from('sproutemail_campaigns_entries_recipientlists')
				->where(array('type' => 'notification', 'entryId' => $entry['id']))
				->queryAll();

			$oldNotifications[$key] = $entry;

			$oldNotifications[$key]['recipientLists'] = $recipientList;
		}

		if (!empty($oldNotifications))
		{
			foreach ($oldNotifications as $oldNotification)
			{
				$insertData = array(
					'id'            => $oldNotification['id'],
					'name'          => $oldNotification['name'],
					'template'      => $oldNotification['template'],
					'eventId'       => $oldNotification['eventId'],
					'options'       => $oldNotification['options'],
					'subjectLine'   => $oldNotification['subjectLine'],
					'recipients'    => $oldNotification['recipients'],
					'fromName'      => $oldNotification['fromName'],
					'fromEmail'     => $oldNotification['fromEmail'],
					'replyToEmail'  => $oldNotification['replyToEmail'],
					'sent'          => $oldNotification['sent'],
					'fieldLayoutId' => $oldNotification['fieldLayoutId'],
					'enableFileAttachments' => $oldNotification['enableFileAttachments'],
				);

				$existNotification = craft()->db->createCommand()
					->select('id')
					->from('sproutemail_notifications')
					->where(array('id' => $oldNotification['id']))
					->queryAll();

				if (!empty($existNotification)) continue;

				craft()->db->createCommand()->insert('sproutemail_notifications', $insertData);


				craft()->db->createCommand()->update('elements', array(
					'type' => 'SproutEmail_NotificationEmail'
				),
					'id= :id', array(':id' => $oldNotification['id'])
				);

				if (!empty($oldNotification['recipientLists']))
				{
					foreach ($oldNotification['recipientLists'] as $recipientList)
					{
						craft()->db->createCommand()->insert('sproutemail_notification_recipientlists', array(
							'notificationId' => $recipientList['entryId'],
							'list'           => $recipientList['list']
						));
					}
				}

				craft()->db->createCommand()->delete('sproutemail_campaigns_entries', array(
					'id' => $oldNotification['id']
				));

				craft()->db->createCommand()->delete('sproutemail_campaigns', array(
					'type' => 'notification'
				));
			}
		}
	}

	private function alterTablesColumns()
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
			if (!craft()->db->tableExists('sproutemail_campaigns'))
			{
				craft()->db->createCommand()->renameTable($tableName, 'sproutemail_campaigns');
			}
		}
	}

	public function dropTables()
	{
		if (craft()->db->tableExists('sproutemail_campaigns_notifications'))
		{
			craft()->db->createCommand()->dropTable('sproutemail_campaigns_notifications');
		}
	}
}
