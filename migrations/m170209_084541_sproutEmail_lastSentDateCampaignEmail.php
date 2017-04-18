<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m170209_084541_sproutEmail_lastSentDateCampaignEmail extends BaseMigration
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
			if (($column = $table->getColumn('lastDateSent')) == null)
			{
				$definition = array(
					AttributeType::Mixed,
					'column'   => ColumnType::DateTime,
					'default'  => null,
					'required' => false
				);

				$this->addColumnBefore('sproutemail_campaignemails', 'lastDateSent', $definition, 'dateCreated');
			}
			else
			{
				Craft::log('Tried to add a `lastDateSent` column to the `sproutemail_campaignemails` table, but there is already one there.', LogLevel::Warning);
			}
		}
		return true;
	}
}
