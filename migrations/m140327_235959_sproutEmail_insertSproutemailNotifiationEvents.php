<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m140327_235959_sproutEmail_insertSproutemailNotifiationEvents extends BaseMigration
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
		        )     
		    );	
		    
		    foreach($data as $entry)
		    {
		        $this->insert($tableName, $entry);
		    }
		}
		else
		{
			Craft::log('Could not find an ' . $tableName . ' table. Wut?', LogLevel::Error);
		}
		
		return true;
	}
}