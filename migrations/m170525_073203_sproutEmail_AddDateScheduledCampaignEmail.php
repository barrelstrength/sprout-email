<?php

namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m170525_073203_sproutEmail_AddDateScheduledCampaignEmail extends BaseMigration
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
			if (($column = $table->getColumn('dateScheduled')) == null)
			{
				$definition = array(
					AttributeType::Mixed,
					'column'   => ColumnType::DateTime,
					'required' => false
				);

				$this->addColumnAfter('sproutemail_campaignemails', 'dateScheduled', $definition, 'dateSent');
			}
			else
			{
				Craft::log('The `dateScheduled` column already exists in the `sproutemail_campaignemails` table.',
					LogLevel::Warning);
			}
		}

		return true;
	}
}
