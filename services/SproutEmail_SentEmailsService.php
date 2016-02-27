<?php

namespace Craft;

/**
 * Class SproutEmail_SentEmails
 *
 * @package Craft
 */

class SproutEmail_SentEmailsService extends BaseApplicationComponent
{
	/**
	 * Save email snapshot using the Sent Email Element Type
	 *
	 * @param       $emailModel
	 * @param array $info
	 *
	 * @throws \Exception
	 */
	public function saveSentEmail($emailModel, $info = array())
	{
		$sentEmail = new SproutEmail_SentEmailModel();

		$sentEmail->title = $emailModel->subject;
		$sentEmail->emailSubject = $emailModel->subject;
		$sentEmail->fromEmail = $emailModel->fromEmail;
		$sentEmail->fromName = $emailModel->fromName;
		$sentEmail->toEmail = $emailModel->toEmail;
		$sentEmail->body = $emailModel->body;
		$sentEmail->htmlBody = $emailModel->htmlBody;

		$sentEmail->getContent()->setAttribute('title', $sentEmail->title);

		$sentEmail->info = $info;

		// @todo - why do we need to set this to blank?
		// Where are Global Sets and other Element Types handling this? They do not
		// add a value for slug in the craft_elements table
		$sentEmail->slug = '';

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

		try
		{
			if (craft()->elements->saveElement($sentEmail))
			{
				$sentEmailRecord = new SproutEmail_SentEmailRecord();

				$sentEmailRecord->setAttributes($sentEmail->getAttributes(), false);

				if ($sentEmailRecord->save())
				{
					if ($transaction != null)
					{
						$transaction->commit();
					}
				}
			}
		}
		catch (\Exception $e)
		{
			if ($transaction != null)
			{
				$transaction->rollback();
			}

			throw $e;
		}
	}

	/**
	 * Get a Sent Email Element Type by ID
	 *
	 * @param $sentEmailId
	 *
	 * @return mixed
	 */
	public function getSentEmailById($sentEmailId)
	{
		return craft()->elements->getElementById($sentEmailId, 'SproutEmail_SentEmail');
	}

	public function getInfoRow($sentEmailId)
	{
		$string = '';
		$sentEmail = $this->getSentEmailById($sentEmailId);

		$infos = $sentEmail->info;

		if (!empty($infos))
		{
			$row = array();
			$dataRow = array();
			foreach ($infos as $infoKey => $info)
			{
				$attribute = str_replace(' ', '', strtolower($infoKey));
				$row[] = '{"label":"' . $infoKey . '","attribute":"' . $attribute . '"}';
				$dataRow[] = "data-" . $attribute . "='" . $info . "'";
			}
			$infoString = "data-inforow='[" . implode(",", $row) . "]'";
			$dataString = implode(' ', $dataRow);
			$string = $infoString . ' ' . $dataString;
		}

		return TemplateHelper::getRaw($string);
	}
}