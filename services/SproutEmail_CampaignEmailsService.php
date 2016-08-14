<?php
namespace Craft;

class SproutEmail_CampaignEmailsService extends BaseApplicationComponent
{
	protected $campaignEmailRecord;

	/**
	 * SproutEmail_CampaignEmailsService constructor.
	 *
	 * @param null $campaignEmailRecord
	 */
	public function __construct($campaignEmailRecord = null)
	{
		$this->campaignEmailRecord = $campaignEmailRecord;

		if (is_null($this->campaignEmailRecord))
		{
			$this->campaignEmailRecord = SproutEmail_CampaignEmailRecord::model();
		}
	}

	/**
	 * @param SproutEmail_CampaignEmailModel $campaignEmail
	 * @param SproutEmail_CampaignTypeModel  $campaign
	 *
	 * @return bool
	 * @throws Exception
	 * @throws \CDbException
	 * @throws \Exception
	 */
	public function saveCampaignEmail(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignTypeModel $campaignType)
	{
		$isNewEntry          = true;
		$campaignEmailRecord = new SproutEmail_CampaignEmailRecord();

		if ($campaignEmail->id && !$campaignEmail->saveAsNew)
		{
			$campaignEmailRecord = SproutEmail_CampaignEmailRecord::model()->findById($campaignEmail->id);
			$isNewEntry          = false;
			if (!$campaignEmailRecord)
			{
				throw new Exception(Craft::t('No entry exists with the ID “{id}”', array('id' => $campaignEmail->id)));
			}
		}

		$campaignEmailRecord->campaignId  = $campaignEmail->campaignId;
		$campaignEmailRecord->subjectLine = $campaignEmail->subjectLine;

		$campaignEmailRecord->setAttributes($campaignEmail->getAttributes());
		$campaignEmailRecord->setAttribute('recipients', $this->getOnTheFlyRecipients());

		$campaignEmailRecord->validate();

		if ($campaignEmail->saveAsNew)
		{
			// Prevent subjectLine to be appended by a number
			$campaignEmailRecord->subjectLine = $campaignEmail->subjectLine;

			$campaignEmail->getContent()->title = $campaignEmail->subjectLine;
		}

		$campaignEmail->addErrors($campaignEmailRecord->getErrors());

		if (!$campaignEmail->hasErrors())
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
			try
			{
				if (craft()->elements->saveElement($campaignEmail))
				{
					// Now that we have an element ID, save it on the other stuff
					if ($isNewEntry)
					{
						$campaignEmailRecord->id = $campaignEmail->id;
					}

					$campaignEmailRecord->save(false);

					$mailer = sproutEmail()->mailers->getMailerByName($campaignType->mailer);

					sproutEmail()->mailers->saveRecipientLists($mailer, $campaignEmail);

					if ($transaction && $transaction->active)
					{
						$transaction->commit();
					}

					return $campaignEmailRecord;
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
	 * @param SproutEmail_CampaignEmailModel $campaignEmail
	 *
	 * @return bool
	 * @throws \CDbException
	 * @throws \Exception
	 */
	public function deleteCampaignEmail(SproutEmail_CampaignEmailModel $campaignEmail)
	{
		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			craft()->elements->deleteElementById($campaignEmail->id);

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
	 * @param int $emailId
	 *
	 * @return SproutEmail_CampaignEmailModel
	 */
	public function getCampaignEmailById($emailId)
	{
		return craft()->elements->getElementById($emailId, 'SproutEmail_CampaignEmail');
	}

	public function getRecipientListsByEmailId($id)
	{
		if (($lists = SproutEmail_RecipientListRelationsRecord::model()->findAllByAttributes(array('emailId' => $id))))
		{
			return SproutEmail_RecipientListRelationsModel::populateModels($lists);
		}
	}

	public function deleteRecipientListsByEmailId($id)
	{
		if (($lists = SproutEmail_RecipientListRelationsRecord::model()->findAllByAttributes(array('emailId' => $id))))
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
	 *
	 * @return SproutEmail_CampaignEmailRecord
	 */
	public function getFieldValue($field, $value)
	{
		$criteria            = new \CDbCriteria();
		$criteria->condition = "{$field} =:value";
		$criteria->params    = array(':value' => $value);
		$criteria->limit     = 1;

		$result = SproutEmail_CampaignEmailRecord::model()->find($criteria);

		return $result;
	}

	/**
	 * @param SproutEmail_CampaignTypeModel $campaign
	 *
	 * @return bool
	 * @throws Exception
	 * @throws \Exception
	 */
	public function saveRelatedCampaignEmail(SproutEmail_CampaignTypeModel $campaign)
	{
		$defaultMailer         = sproutEmail()->mailers->getMailerByName('defaultmailer');
		$defaultMailerSettings = $defaultMailer->getSettings();

		$campaignEmail = new SproutEmail_CampaignEmailModel();

		$campaignEmail->campaignId   = $campaign->getAttribute('id');
		$campaignEmail->dateCreated  = date('Y-m-d H:i:s');
		$campaignEmail->enabled      = true;
		$campaignEmail->saveAsNew    = true;
		$campaignEmail->fromName     = $defaultMailerSettings->fromName;
		$campaignEmail->fromEmail    = $defaultMailerSettings->fromEmail;
		$campaignEmail->replyToEmail = $defaultMailerSettings->replyToEmail;
		$campaignEmail->recipients   = null;
		$campaignEmail->subjectLine  = $campaign->getAttribute('name');

		return sproutEmail()->campaignEmails->saveCampaignEmail($campaignEmail, $campaign);
	}

	/**
	 * @param EmailModel $email
	 * @param string     $template
	 */
	public function showCampaignEmail(EmailModel $email, $fileExtension = 'html')
	{
		if ($fileExtension == 'txt')
		{
			$output = $email->body;
		}
		else
		{
			$output = $email->htmlBody;
		}

		// Output it into a buffer, in case TasksService wants to close the connection prematurely
		ob_start();
		echo $output;

		// End the request
		craft()->end();
	}
}
