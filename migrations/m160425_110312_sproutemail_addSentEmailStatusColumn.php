<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m160425_110312_sproutemail_addSentEmailStatusColumn extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		if (($table = $this->dbConnection->schema->getTable('{{sproutemail_sentemail}}')))
		{
			if (($column = $table->getColumn('status')) == null)
			{
				$definition = array(
					AttributeType::Mixed,
					'column'   => ColumnType::Text,
					'required' => false
				);

				$this->addColumnAfter('sproutemail_sentemail', 'status', $definition, 'info');
			}
			else
			{
				Craft::log('Tried to add a `status` column to the `sproutemail_sentemail` table, but there is already
				one there.', LogLevel::Warning);
			}
		}
		else
		{
			Craft::log('Could not find the `sproutemail_sentemail` table.', LogLevel::Error);
		}

		return true;
	}
}
