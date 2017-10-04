<?php
namespace Craft;

class m150407_160000_sproutEmail_addsRecipientsColumnToEntriesTable extends BaseMigration
{
	/**
	 * @return bool
	 */
	public function safeUp()
	{
		if (($table = $this->dbConnection->schema->getTable('{{sproutemail_campaigns_entries}}')))
		{
			if (($column = $table->getColumn('recipients')) == null)
			{
				$definition = array(
					AttributeType::Mixed,
					'column'   => ColumnType::Text,
					'required' => false
				);

				$this->addColumnAfter('sproutemail_campaigns_entries', 'recipients', $definition, 'campaignId');
			}
			else
			{
				Craft::log('Tried to add a `recipients` column to the `sproutemail_campaigns_entries` table, but there is already one there.', LogLevel::Warning);
			}
		}
		else
		{
			Craft::log('Could not find the `sproutemail_campaigns_entries` table.', LogLevel::Error);
		}

		return true;
	}
}
