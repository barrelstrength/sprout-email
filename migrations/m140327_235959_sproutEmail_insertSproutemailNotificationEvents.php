<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m140327_235959_sproutEmail_insertSproutEmailNotificationEvents extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{		
			$tableName = 'sproutemail_notification_events';
		$table = $this->dbConnection->schema->getTable('{{' . $tableName . '}}');

		if ($table)
		{  
			Craft::log('Inserting into `' . $tableName, LogLevel::Info, true);
			
			$data = array(
				array(
					'registrar' => 'craft',
					'event' => 'userSession.beforeLogin',
					'description' => 'Craft: Before a user logs in'
				),
				array(
					'registrar' => 'craft',
					'event' => 'userSession.login',
					'description' => 'Craft: When a user logs in'
				),
				array(
					'registrar' => 'craft',
					'event' => 'users.beforeActivateUser',
					'description' => 'Craft: Before a user is activated'
				),
				array(
					'registrar' => 'craft',
					'event' => 'users.activateUser',
					'description' => 'Craft: When a user is activated'
				),
				array(
					'registrar' => 'craft',
					'event' => 'users.beforeUnlockUser',
					'description' => 'Craft: Before a user is unlocked'
				),
				array(
					'registrar' => 'craft',
					'event' => 'users.unlockUser',
					'description' => 'Craft: When a user is unlocked'
				),
				array(
					'registrar' => 'craft',
					'event' => 'users.beforeSuspendUser',
					'description' => 'Craft: Before a user is suspended'
				),
				array(
					'registrar' => 'craft',
					'event' => 'users.suspendUser',
					'description' => 'Craft: When a user is suspended'
				),
				array(
					'registrar' => 'craft',
					'event' => 'users.beforeUnsuspendUser',
					'description' => 'Craft: Before a user is unsuspended'
				),
				array(
					'registrar' => 'craft',
					'event' => 'users.unsuspendUser',
					'description' => 'Craft: When a user is unsuspended'
				),
				array(
					'registrar' => 'craft',
					'event' => 'assets.saveFileContent',
					'description' => 'Craft: When file content is saved'
				),
				array(
					'registrar' => 'craft',
					'event' => 'assets.saveContent',
					'description' => 'Craft: When content is saved'
				),
				array(
					'registrar' => 'craft',
					'event' => 'globals.saveGlobalContent',
					'description' => 'Craft: When global content is saved'
				),
				array(
					'registrar' => 'craft',
					'event' => 'plugins.loadPlugins',
					'description' => 'Craft: When plugins are loaded'
				),
				array(
					'registrar' => 'craft',
					'event' => 'tags.saveTag',
					'description' => 'Craft: When a tag is saved'
				),
				array(
					'registrar' => 'craft',
					'event' => 'tags.saveTagContent',
					'description' => 'Craft: When tag content is saved'
				),
				array(
					'registrar' => 'craft',
					'event' => 'updates.beginUpdate',
					'description' => 'Craft: When an update is started'
				),
				array(
					'registrar' => 'craft',
					'event' => 'updates.endUpdate',
					'description' => 'Craft: When an update is finished'
				),
				array(
					'registrar' => 'craft',
					'event' => 'users.beforeDeleteUser',
					'description' => 'Craft: Before a user is deleted'
				),
				array(
					'registrar' => 'craft',
					'event' => 'users.deleteUser',
					'description' => 'Craft: When a user is deleted'
				),
				array(
					'registrar' => 'craft',
					'event' => 'users.beforeVerifyUser',
					'description' => 'Craft: Before a user is verified'
				),
				array(
					'registrar' => 'craft',
					'event' => 'users.beforeSaveUser',
					'description' => 'Craft: Before a user is saved'
				)
			);	
				
			foreach($data as $entry)
			{
				$exists = craft()->db->createCommand()
												->select('*')
												->from('sproutemail_notification_events')
												->where('event=:event', array( ':event' => $entry['event'] ))
												->queryRow();

				if ( ! $exists )
				{
					$this->insert($tableName, $entry);	
				}
			}
		}
		else
		{
			Craft::log('Could not find an ' . $tableName . ' table. Wut?', LogLevel::Error);
		}
		
		return true;
	}
}