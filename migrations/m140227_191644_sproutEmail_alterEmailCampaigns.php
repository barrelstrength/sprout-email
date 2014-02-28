<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m140227_191644_sproutEmail_alterEmailCampaigns extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{		
	    $tableName = 'sproutemail_campaigns';
		$table = $this->dbConnection->schema->getTable('{{' . $tableName . '}}');

		if ($table)
		{  
			// alter sproutemails_campaigns.htmlBody from varchar(255) to text
		    $field = 'htmlBody';
			if ($table->getColumn($field))
			{
			    Craft::log('Altering `' . $tableName . '.' . $field . '` to type text', LogLevel::Info, true);
			
			    $this->alterColumn($tableName, $field, 'TEXT');			
			}
			else
			{
			    Craft::log('Error Altering `' . $tableName . '.' . $field . '` to type text.  Column does not exist.', LogLevel::Warning);
			}
			
			// alter sproutemails_campaigns.textBody from varchar(255) to text
			$field = 'textBody';
			if ($table->getColumn($field))
			{
			    Craft::log('Altering `' . $tableName . '.' . $field . '` to type text', LogLevel::Info, true);
			    	
			    $this->alterColumn($tableName, $field, 'TEXT');
			}
			else
			{
			    Craft::log('Error Altering `' . $tableName . '.' . $field . '` to type text.  Column does not exist.', LogLevel::Warning);
			}
			
			// alter sproutemails_campaigns.recipients from varchar(255) to text
			$field = 'recipients';
			if ($table->getColumn($field))
			{
			    Craft::log('Altering `' . $tableName . '.' . $field . '` to type text', LogLevel::Info, true);
			
			    $this->alterColumn($tableName, $field, 'TEXT');
			}
			else
			{
			    Craft::log('Error Altering `' . $tableName . '.' . $field . '` to type text.  Column does not exist.', LogLevel::Warning);
			}
		}
		else
		{
			Craft::log('Could not find an ' . $tableName . ' table. Wut?', LogLevel::Error);
		}
		
		return true;
	}
}