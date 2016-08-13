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
		$this->modifyRecipientsTable();

		$this->createNotificationTables();

		$this->moveOldNotifications();

		$this->updateCampaignTables();

		$this->dropTables();

		return true;
	}


	private function modifyRecipientsTable()
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
	}

	private function createNotificationTables()
	{
		SproutEmailPlugin::log('Creating the sproutemail_notifications table');

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
					                                 'default'   => false,
					                                 'required'  => true,
					                                 'column'    => 'tinyint',
					                                 'unsigned'  => true),
					'enableFileAttachments' => array('maxLength' => 1,
					                                 'default'   => false,
					                                 'required'  => true,
					                                 'column'    => 'tinyint',
					                                 'unsigned'  => true),
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
				SproutEmailPlugin::log('Error creating the sproutemail_notifications table: ' . $e->getMessage());
			}
		}

		SproutEmailPlugin::log('Finished creating the sproutemail_notifications table');
	}

	private function moveOldNotifications()
	{
		SproutEmailPlugin::log('Moving all notifications to the sproutemail_notifications table');

		if (!craft()->db->tableExists('sproutemail_campaigns') ||
			!craft()->db->tableExists('sproutemail_campaigns_notifications') ||
			!craft()->db->tableExists('sproutemail_campaigns_entries')
		)
		{
			return false;
		}

		$entries = craft()->db->createCommand()
			->select('*')
			->from('sproutemail_campaigns setting')
			->join('sproutemail_campaigns_notifications notification', 'setting.id = notification.campaignId')
			->join('sproutemail_campaigns_entries email', 'setting.id = email.campaignId')
			->where(array('type' => 'notification'))
			->queryAll();

		$oldNotifications = array();

		if (!empty($entries))
		{
			foreach ($entries as $key => $entry)
			{
				$oldNotifications[$key] = $entry;
			}
		}

		if (!empty($oldNotifications))
		{
			foreach ($oldNotifications as $oldNotification)
			{
				$insertData = array(
					'id'                    => $oldNotification['id'],
					'name'                  => $oldNotification['name'],
					'template'              => $oldNotification['template'],
					'eventId'               => $oldNotification['eventId'],
					'options'               => $oldNotification['options'],
					'subjectLine'           => $oldNotification['subjectLine'],
					'recipients'            => $oldNotification['recipients'],
					'fromName'              => $oldNotification['fromName'],
					'fromEmail'             => $oldNotification['fromEmail'],
					'replyToEmail'          => $oldNotification['replyToEmail'],
					'sent'                  => $oldNotification['sent'],
					'fieldLayoutId'         => $oldNotification['fieldLayoutId'],
					'enableFileAttachments' => $oldNotification['enableFileAttachments'],
				);

				if (craft()->db->tableExists('sproutemail_notifications'))
				{
					$existNotification = craft()->db->createCommand()
						->select('id')
						->from('sproutemail_notifications')
						->where(array('id' => $oldNotification['id']))
						->queryAll();

					if (!empty($existNotification))
					{
						continue;
					}

					craft()->db->createCommand()->insert('sproutemail_notifications', $insertData);
				}

				craft()->db->createCommand()->update('elements', array(
					'type' => 'SproutEmail_NotificationEmail'
				),
					'id= :id', array(':id' => $oldNotification['id'])
				);

				// Remove old notifications data
				craft()->db->createCommand()->delete('sproutemail_campaigns_entries', array(
					'id' => $oldNotification['id']
				));

				craft()->db->createCommand()->delete('sproutemail_campaigns', array(
					'type' => 'notification'
				));
			}
		}

		SproutEmailPlugin::log('Finished moving all notifications to the sproutemail_notifications table');
	}

	private function updateCampaignTables()
	{
		SproutEmailPlugin::log('Updating sproutemail_campaigns_entries table to sproutemail_campaigns table');

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

		$tableName = "sproutemail_campaigns_entries";

		if (craft()->db->tableExists($tableName) && !craft()->db->tableExists('sproutemail_campaigns'))
		{
			SproutEmailPlugin::log('Rename sproutemail_campaigns_entries table to sproutemail_campaigns');

			craft()->db->createCommand()->renameTable($tableName, 'sproutemail_campaigns');
		}

		SproutEmailPlugin::log('Finished updating sproutemail_campaigns_entries table to sproutemail_campaigns table');
	}

	private function dropTables()
	{
		if (craft()->db->tableExists('sproutemail_campaigns_notifications'))
		{
			SproutEmailPlugin::log('Remove sproutemail_campaigns_notifications table');

			craft()->db->createCommand()->dropTable('sproutemail_campaigns_notifications');
		}
	}
}
