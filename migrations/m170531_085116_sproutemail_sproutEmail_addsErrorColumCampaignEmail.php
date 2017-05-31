<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m170531_085116_sproutemail_sproutEmail_addsErrorColumCampaignEmail extends BaseMigration
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
			if (($column = $table->getColumn('error')) == null)
			{
				$definition = array(
					AttributeType::Mixed,
					'column'   => ColumnType::Bool,
					'required' => false
				);

				$this->addColumnAfter('sproutemail_campaignemails', 'error', $definition, 'sendDate');
			}
			else
			{
				Craft::log('Tried to add a `schema` column to the `sproutemail_campaignemails` table, but there is already one there.', LogLevel::Warning);
			}
		}
		return true;
	}
}
