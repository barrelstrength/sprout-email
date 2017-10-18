<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m170607_110602_sproutemail_AddEmailSettingsCampaignEmail extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		if (($table = $this->dbConnection->schema->getTable('{{sproutemail_campaignemails}}')))
		{
			if (($column = $table->getColumn('emailSettings')) == null)
			{
				$definition = array(
					AttributeType::Mixed,
					'column'   => ColumnType::Text,
					'default'  => null,
					'required' => false
				);

				$this->addColumnBefore('sproutemail_campaignemails', 'emailSettings', $definition, 'recipients');
			}
			else
			{
				Craft::log('The `emailSettings` column already exists in the `sproutemail_campaignemails` table.',
					LogLevel::Warning);
			}
		}

		return true;
	}
}
