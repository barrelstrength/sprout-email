<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m160804_000002_sproutEmail_createNotificationTables extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		SproutEmailPlugin::log('Creating the sproutemail_notificationemails table');

		$tableName = 'sproutemail_notificationemails';

		if (!craft()->db->tableExists($tableName))
		{
			try
			{
				// Create the sproutemail_notificationemails table
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

				// Add foreign keys to sproutemail_notificationemails
				craft()->db->createCommand()->addForeignKey($tableName, 'id', 'elements', 'id', 'CASCADE', null);
				craft()->db->createCommand()->addForeignKey($tableName, 'fieldLayoutId', 'fieldlayouts',
					'id', 'SET NULL', null);
			}
			catch (\Exception $e)
			{
				SproutEmailPlugin::log('Error creating the sproutemail_notificationemails table: ' . $e->getMessage());
			}
		}

		SproutEmailPlugin::log('Finished creating the sproutemail_notificationemails table');

		return true;
	}
}
