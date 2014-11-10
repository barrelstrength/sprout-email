<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m140904_185559_sproutEmail_alterEmailTemplateSettings extends BaseMigration
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
			$field = 'subjectHandle';
		
			$this->addColumnAfter( $tableName, $field, array (
				AttributeType::String,
				'required' => false 
			), 'textBody' );
		}
		else
		{
			Craft::log('Could not find an ' . $tableName . ' table. Wut?', LogLevel::Error);
		}
		
		return true;
	}
}
