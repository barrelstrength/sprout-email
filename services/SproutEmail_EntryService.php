<?php
namespace Craft;

class SproutEmail_EntryService extends BaseApplicationComponent
{
	protected $entryRecord;

	/**
	 * Constructor
	 * 
	 * @param object $entryRecord
	 */
	public function __construct($entryRecord = null)
	{
		$this->entryRecord = $entryRecord;
		if (is_null($this->entryRecord)) {
			$this->entryRecord = SproutEmail_EntryRecord::model();
		}
	}
	
	/**
	 * Saves a entry.
	 *
	 * @param SproutEmail_EntryRecord $entry
	 * @throws \Exception
	 * @return bool
	 */
	public function saveEntry(SproutEmail_EntryModel $entry)
	{	
		$isNewEntry = !$entry->id;

		if ($entry->id)
		{
			$entryRecord = SproutEmail_EntryRecord::model()->findById($entry->id);

			if (!$entryRecord)
			{
				throw new Exception(Craft::t('No entry exists with the ID “{id}”', array('id' => $entry->id)));
			}

			$oldEntry = SproutEmail_EntryModel::populateModel($entryRecord);
		}
		else
		{
			$entryRecord = new SproutEmail_EntryRecord();
		}

		$entryRecord->campaignId = $entry->campaignId;
		$entryRecord->subjectLine = $entry->subjectLine;

		$entryRecord->validate();
		$entry->addErrors($entryRecord->getErrors());

		if (!$entry->hasErrors())
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
			try
			{	
				if (craft()->elements->saveElement($entry))
				{
					// Now that we have an element ID, save it on the other stuff
					if ($isNewEntry)
					{
						$entryRecord->id = $entry->id;
					}

					// Save our Entry Settings
					$entryRecord->save(false);

					if ($transaction !== null)
					{
						$transaction->commit();
					}

					return true;
				}
			}
			catch (\Exception $e)
			{
				if ($transaction !== null)
				{
					$transaction->rollback();
				}

				throw $e;
			}
		}

		return false;
	}

	/**
	 * Deletes an Entry
	 * 
	 * @param int $id
	 * @return boolean
	 */
	public function deleteEntry(SproutEmail_EntryModel $entry)
	{
		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			// Delete the Element and Entry
			craft()->elements->deleteElementById($entry->id);

			if ($transaction !== null)
			{
				$transaction->commit();
			}

			return true;
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}
	}

	/**
	 * Return Entry by id
	 * 
	 * @param int $id
	 * @return object
	 */
	public function getEntryById($entryId)
	{
		return craft()->elements->getElementById($entryId, 'SproutEmail_Entry');
	}
}
