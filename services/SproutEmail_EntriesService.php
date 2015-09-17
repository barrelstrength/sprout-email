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

	/**
	 * @param SproutEmail_EntryModel    $entry
	 * @param SproutEmail_CampaignModel $campaign
	 *
	 * @throws Exception
	 * @throws \CDbException
	 * @throws \Exception
	 *
	 * @return bool
	 */
	public function saveEntry(SproutEmail_EntryModel $entry, SproutEmail_CampaignModel $campaign)
	{
		$isNewEntry = true;
		$entryRecord = new SproutEmail_EntryRecord();

		if ($entry->id && !$entry->saveAsNew)
		{
			$entryRecord = SproutEmail_EntryRecord::model()->findById($entry->id);
			$isNewEntry = false;
			if (!$entryRecord)
			{
				throw new Exception(Craft::t('No entry exists with the ID “{id}”', array('id' => $entry->id)));
			}
		}

		$entryRecord->campaignId  = $entry->campaignId;
		$entryRecord->subjectLine = $entry->subjectLine;

		$entryRecord->setAttributes($entry->getAttributes());
		$entryRecord->setAttribute('recipients', $this->getOnTheFlyRecipients());

		$entryRecord->validate();

		if($entry->saveAsNew)
		{
			$entry->subjectLine = $entryRecord->subjectLine;
		}

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

					$notificationEvent = craft()->request->getPost('notificationEvent');

					sproutEmail()->mailers->saveRecipientLists($campaign, $entry);

					if ((!$notificationEvent || sproutEmail()->notifications->save($notificationEvent, $campaign->id)))
					{
						if ($transaction && $transaction->active)
						{
							$transaction->commit();
						}

						return true;
					}
				}
			}
			catch (\Exception $e)
			{
				if ($transaction && $transaction->active)
				{
					$transaction->rollback();
				}

				throw $e;
			}
		}

		return false;
	}

	/**
	 * @param SproutEmail_EntryModel $entry
	 *
	 * @throws \CDbException
	 * @throws \Exception
	 *
	 * @return bool
	 */
	public function deleteEntry(SproutEmail_EntryModel $entry)
	{
		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			// Delete Campaign and Rules associated with this email
			if ($entry->getType() == Campaign::Notification)
			{
				sproutEmail()->campaigns->deleteCampaign($entry->campaignId);
			}

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
	 * @param int $entryId
	 *
	 * @return SproutEmail_EntryModel
	 */
	public function getEntryById($entryId)
	{
		return craft()->elements->getElementById($entryId, 'SproutEmail_Entry');
	}

	public function getRecipientListsByEntryId($id)
	{
		if (($lists = SproutEmail_EntryRecipientListRecord::model()->findAllByAttributes(array('entryId' => $id))))
		{
			return SproutEmail_EntryRecipientListModel::populateModels($lists);
		}
	}

	public function deleteRecipientListsByEntryId($id)
	{
		if (($lists = SproutEmail_EntryRecipientListRecord::model()->findAllByAttributes(array('entryId' => $id))))
		{
			foreach ($lists as $list)
			{
				$list->delete();
			}
		}
	}

	/**
	 * @return string
	 */
	protected function getOnTheFlyRecipients()
	{
		return craft()->request->getPost('recipient.onTheFlyRecipients');
	}

	/**
	 * Returns the value of a given field
	 *
	 * @param string $field
	 * @param string $value
	 * @return SproutEmail_EntryRecord
	 */
	public function getFieldValue($field, $value)
	{
		$criteria = new \CDbCriteria();
		$criteria->condition = "{$field} =:value";
		$criteria->params = array(':value'=>$value);
		$criteria->limit = 1;

		$result = SproutEmail_EntryRecord::model()->find($criteria);
		return $result;
	}
}
