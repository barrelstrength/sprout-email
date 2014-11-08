<?php
namespace Craft;

class SproutEmail_EmailBlastService extends BaseApplicationComponent
{
	protected $emailBlastRecord;

	/**
	 * Constructor
	 * 
	 * @param object $emailBlastRecord
	 */
	public function __construct($emailBlastRecord = null)
	{
		$this->emailBlastRecord = $emailBlastRecord;
		if (is_null($this->emailBlastRecord)) {
			$this->emailBlastRecord = SproutEmail_EmailBlastRecord::model();
		}
	}
	
	/**
	 * Saves a entry.
	 *
	 * @param SproutEmail_EmailBlastRecord $emailBlast
	 * @throws \Exception
	 * @return bool
	 */
	public function saveEmailBlast(SproutEmail_EmailBlastModel $emailBlast)
	{	
		$isNewEmailBlast = !$emailBlast->id;

		if ($emailBlast->id)
		{
			$emailBlastRecord = SproutEmail_EmailBlastRecord::model()->findById($emailBlast->id);

			if (!$emailBlastRecord)
			{
				throw new Exception(Craft::t('No entry exists with the ID “{id}”', array('id' => $emailBlast->id)));
			}

			$oldEmailBlast = SproutEmail_EmailBlastModel::populateModel($emailBlastRecord);
		}
		else
		{
			$emailBlastRecord = new SproutEmail_EmailBlastRecord();
		}

		$emailBlastRecord->emailBlastTypeId = $emailBlast->emailBlastTypeId;
		$emailBlastRecord->subjectLine = $emailBlast->subjectLine;

		$emailBlastRecord->validate();
		$emailBlast->addErrors($emailBlastRecord->getErrors());

		if (!$emailBlast->hasErrors())
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
			try
			{	
				if (craft()->elements->saveElement($emailBlast))
				{
					// Now that we have an element ID, save it on the other stuff
					if ($isNewEmailBlast)
					{
						$emailBlastRecord->id = $emailBlast->id;
					}

					// Save our Entry Settings
					$emailBlastRecord->save(false);

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
	 * Deletes an email blast
	 * 
	 * @param int $id
	 * @return boolean
	 */
	public function deleteEmailBlast(SproutEmail_EmailBlastRecord $emailBlast)
	{
		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			// Delete the Element and Entry
			craft()->elements->deleteElementById($emailBlast->id);

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
	 * Return email blast by id
	 * 
	 * @param int $id
	 * @return object
	 */
	public function getEmailBlastById($id)
	{
		$emailBlastRecord = $this->emailBlastRecord->findById($id);
		
		if ($emailBlastRecord) 
		{
			return SproutEmail_EmailBlastModel::populateModel($emailBlastRecord);
		} 
		else 
		{
			return null;
		}
	}
}
