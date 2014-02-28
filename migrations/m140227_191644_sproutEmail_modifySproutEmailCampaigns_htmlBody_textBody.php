<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m140227_191644_sproutEmail_modifySproutEmailCampaigns_htmlBody_textBody extends BaseMigration
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
		    $field = 'htmlBody';
		    
			// add sproutforms_forms.notification_notification_subject
			if ($table->getColumn($field) == null)
			{
			    Craft::log('Altering `' . $tableName . '.' . $field . '` to type text', LogLevel::Info, true);
			
			    $this->alterColumn($tableName, $field, array(AttributeType::String, 'text');			
			}
			else
			{
			    Craft::log('Error Altering `' . $tableName . '.' . $field . '` to type text.  Column does not exist.', LogLevel::Warning);
			}
			
			$field = 'textBody';
			
			// add sproutforms_forms.notification_notification_subject
			if ($table->getColumn($field) == null)
			{
			    Craft::log('Altering `' . $tableName . '.' . $field . '` to type text', LogLevel::Info, true);
			    	
			    $this->alterColumn($tableName, $field, array(AttributeType::String, 'text');
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