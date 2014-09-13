<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m140912_235959_sproutEmail_insertCopyPasteSettings extends BaseMigration
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
                        'id' => 5,
                        'emailProvider' => 'CopyPaste',
                        'apiSettings' => ''
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