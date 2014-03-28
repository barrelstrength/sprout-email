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
		            'registrar' => 'craft',
		            'event' => 'userSession.beforeLogin',
		            'description' => 'Craft: Before a user logs in'
		    );		    	
		    $this->insert($tableName, $data);
		    
		    $data = array(
		            'registrar' => 'craft',
		            'event' => 'userSession.login',
		            'description' => 'Craft: When a user logs in'
		    );	
		    $this->insert($tableName, $data);
		}
		else
		{
			Craft::log('Could not find an ' . $tableName . ' table. Wut?', LogLevel::Error);
		}
		
		return true;
	}
}