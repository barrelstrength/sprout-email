<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m140626_235959_sproutEmail_insertNotificationEvents extends BaseMigration
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
          array (
            'registrar' => 'craft',
            'event' => 'cron',
            'description' => 'Send email via Cron Job'
          ),
		    );	
		    
		    foreach($data as $entry)
		    {
		    	$exists = craft()->db->createCommand()
		    									->select('*')
    											->from('sproutemail_notification_events')
    											->where('event=:event', array( ':event' => 'cron' ))
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