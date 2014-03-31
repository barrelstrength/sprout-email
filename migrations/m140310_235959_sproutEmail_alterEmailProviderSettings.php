<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m140310_235959_sproutEmail_alterEmailProviderSettings extends BaseMigration
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
		    $field = 'apiSettings';
			if ($table->getColumn($field))
			{
			    Craft::log('Altering `' . $tableName . '.' . $field . '` to type text', LogLevel::Info, true);
			
			    $this->alterColumn($tableName, $field, 'TEXT');			
			}
			else
			{
			    Craft::log('Error Altering `' . $tableName . '.' . $field . '` to type text.  Column does not exist.', LogLevel::Warning);
			}
			
			$field = 'fromEmail';
			if ($table->getColumn($field))
			{
			    Craft::log('Dropping `' . $tableName . '.' . $field . '`', LogLevel::Info, true);
			    	
			    $this->dropColumn($tableName, $field);
			}
			else
			{
			    Craft::log('Error Dropping `' . $tableName . '.' . $field . '`.  Column does not exist.', LogLevel::Warning);
			}
			
			$field = 'name';
			if ($table->getColumn($field))
			{
			    Craft::log('Dropping `' . $tableName . '.' . $field . '`', LogLevel::Info, true);
			
			    $this->dropColumn($tableName, $field);
			}
			else
			{
			    Craft::log('Error Dropping `' . $tableName . '.' . $field . '`.  Column does not exist.', LogLevel::Warning);
			}

		    Craft::log('Inserting into `' . $tableName, LogLevel::Info, true);
		    
		    $data = array(
		            'emailProvider' => 'CampaignMonitor',
		            'apiSettings' => '{"client_id":"","api_key":""}',
		            'dateCreated' => '2014-03-10 21:00:00'
		    );		    	
		    $this->insert($tableName, $data);
		    
		    $data = array(
		            'emailProvider' => 'MailChimp',
		            'apiSettings' => '{"api_key":""}',
		            'dateCreated' => '2014-03-10 21:00:00'
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