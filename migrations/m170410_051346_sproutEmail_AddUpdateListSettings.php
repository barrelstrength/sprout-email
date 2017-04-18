<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m170410_051346_sproutEmail_AddUpdateListSettings extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$this->addListSettings();

		$this->updateListSettings();
		$this->updateListSettings('sproutemail_notificationemails');

		$this->deleteEntryRecipientTable();

		return true;
	}

	private function addListSettings()
	{
		if (($table = $this->dbConnection->schema->getTable('{{sproutemail_campaignemails}}')))
		{
			if (($column = $table->getColumn('listSettings')) == null)
			{
				$definition = array(
					AttributeType::Mixed,
					'column'   => ColumnType::Text,
					'required' => false
				);

				$this->addColumnAfter('sproutemail_campaignemails', 'listSettings', $definition, 'recipients');
			}
			else
			{
				Craft::log('Tried to add a `listSettings` column to the `sproutemail_campaignemails` table, but there is already one there.', LogLevel::Warning);
			}
		}

		if (($table = $this->dbConnection->schema->getTable('{{sproutemail_notificationemails}}')))
		{
			if (($column = $table->getColumn('listSettings')) == null)
			{
				$definition = array(
					AttributeType::Mixed,
					'column'   => ColumnType::Text,
					'required' => false
				);

				$this->addColumnAfter('sproutemail_notificationemails', 'listSettings', $definition, 'recipients');
			}
			else
			{
				Craft::log('Tried to add a `listSettings` column to the `sproutemail_notificationemails` table, but there is already one there.', LogLevel::Warning);
			}
		}
	}

	private function updateListSettings($table = 'sproutemail_campaignemails')
	{
		$entries = craft()->db->createCommand()
			->select('*')
			->from($table)
			->queryAll();

		if (!empty($entries))
		{
			foreach ($entries as $entry)
			{
				$id = $entry['id'];

				$lists = $this->getLists();

				if (isset($lists[$id]))
				{
					$listIds     = array('listIds' => $lists[$id]);
					$listIdsJson = json_encode($listIds);

					craft()->db->createCommand()->update($table, array(
							'listSettings' => $listIdsJson
						), 'id= :id', array(':id' => $id)
					);
				}
			}
		}
	}

	private function getLists()
	{
		$entries = craft()->db->createCommand()
			->select('*')
			->from('sproutemail_campaigns_entries_recipientlists')
			->queryAll();

		$lists = array();

		if (!empty($entries))
		{
			foreach ($entries as $entry)
			{
				$emailId = $entry['emailId'];

				$lists[$emailId][] = $entry['list'];
			}
		}

		return $lists;
	}

	private function deleteEntryRecipientTable()
	{
		if (craft()->db->tableExists('sproutemail_campaigns_entries_recipientlists'))
		{
			SproutEmailPlugin::log('Remove sproutemail_campaigns_entries_recipientlists table');

			craft()->db->createCommand()->dropTable('sproutemail_campaigns_entries_recipientlists');
		}
	}
}
