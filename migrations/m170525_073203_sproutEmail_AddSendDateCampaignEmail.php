<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m170525_073203_sproutEmail_AddSendDateCampaignEmail extends BaseMigration
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
			if (($column = $table->getColumn('sendDate')) == null)
			{
				$definition = array(
					AttributeType::Mixed,
					'column'   => ColumnType::DateTime,
					'required' => false
				);

				$this->addColumnAfter('sproutemail_campaignemails', 'sendDate', $definition, 'lastDateSent');
			}
			else
			{
				Craft::log('Tried to add a `sendDate` column to the `sproutemail_campaignemails` table, but there is already one there.', LogLevel::Warning);
			}
		}
		return true;
	}
}
