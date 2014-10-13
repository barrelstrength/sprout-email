<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m140719_235959_sproutEmail_insertSendGridSettings extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{		
	    $tableName = 'sproutemail_email_provider_settings';
		$table = $this->dbConnection->schema->getTable('{{' . $tableName . '}}');

		if ($table)
		{  
		    Craft::log('Inserting into `' . $tableName, LogLevel::Info, true);
		    
		    $data = array(
                array (
                        'id' => 3,
                        'emailProvider' => 'SendGrid',
                        'apiSettings' => '{"api_user":"","api_key":""}'
                ),
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