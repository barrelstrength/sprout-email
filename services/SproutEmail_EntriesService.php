<?php
namespace Craft;

class SproutEmail_EntriesService extends BaseApplicationComponent
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

		if (is_null($this->entryRecord))
		{
			$this->entryRecord = SproutEmail_EntryRecord::model();
		}
	}

	public function saveEntry(SproutEmail_EntryModel $entry, SproutEmail_CampaignModel $campaign)
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

		$entryRecord->campaignId  = $entry->campaignId;
		$entryRecord->subjectLine = $entry->subjectLine;

		$entryRecord->setAttributes($entry->getAttributes());

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

					$entryRecord->save(false);

					if (sproutEmail()->mailers->saveRecipientList($campaign, $entry))
					{
						if ($transaction !== null)
						{
							$transaction->commit();
						}

						return true;
					}
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
	 *
	 * @return object
	 */
	public function getEntryById($entryId)
	{
		return craft()->elements->getElementById($entryId, 'SproutEmail_Entry');
	}
}
