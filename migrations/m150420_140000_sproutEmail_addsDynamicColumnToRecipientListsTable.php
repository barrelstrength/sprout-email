<?php

namespace Craft;

class m150420_140000_sproutEmail_addsDynamicColumnToRecipientListsTable extends BaseMigration
{
	/**
	 * @return bool
	 */
	public function safeUp()
	{
		if (($table = $this->dbConnection->schema->getTable('{{sproutemail_defaultmailer_recipientlists}}')))
		{
			if (($column = $table->getColumn('dynamic')) == null)
			{
				$definition = array(
					AttributeType::Bool,
					'column'   => ColumnType::TinyInt,
					'default'  => 0,
					'required' => false
				);

				$this->addColumnAfter('sproutemail_defaultmailer_recipientlists', 'dynamic', $definition, 'handle');
			}
			else
			{
				Craft::log('Tried to add a `dynamic` column to the `sproutemail_defaultmailer_recipientlists` table, but there is already one there.', LogLevel::Warning);
			}
		}
		else
		{
			Craft::log('Could not find the `sproutemail_defaultmailer_recipientlists` table.', LogLevel::Error);
		}

		return true;
	}
}
