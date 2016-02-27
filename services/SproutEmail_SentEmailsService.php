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
	 * Stores sent email on Sent Email Element type
	 *
	 * @param        $sproutEmailEntry
	 * @param        $emailModel
	 * @param string $type
	 *
	 * @throws Exception
	 * @throws \CDbException
	 * @throws \Exception
	 */
	public function logSentEmail($emailModel, $info = array())
	{

		$sentModel = new SproutEmail_SentEmailModel();
		// validate the element type & throw an exception if it fails
		$element = craft()->elements->getElementType($sentModel->getElementType());
		if (!$element)
		{
			throw new Exception(Craft::t('The {t} element type is not available.',
				array('t' => $model->getElementType())
			));
		}

		$sentModel->info = $info;

		// set global Element attributes
		$sentModel->uri = '';
		$sentModel->slug = '';
		$sentModel->archived = false;
		$sentModel->localeEnabled = $element->isLocalized();

		$sentModel->title = $emailModel->subject;
		$sentModel->emailSubject = $emailModel->subject;
		$sentModel->fromEmail = $emailModel->fromEmail;
		$sentModel->fromName = $emailModel->fromName;
		$sentModel->toEmail = $emailModel->toEmail;
		$sentModel->body = $emailModel->body;
		$sentModel->htmlBody = $emailModel->htmlBody;

		$sentModel->getContent()->setAttribute('title', $sentModel->title);

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

		try
		{
			if (craft()->elements->saveElement($sentModel))
			{
				$sentRecord = new SproutEmail_SentEmailRecord();

				// set the Records attributes
				$sentRecord->setAttributes($sentModel->getAttributes(), false);
				if ($sentRecord->save())
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

	/**
	 * Event for send notification
	 *
	 * @param $vars
	 *
	 * @throws \CException
	 */
	public function onSendNotification($vars)
	{
		$event = new Event($this, $vars);
		$this->raiseEvent('onSendNotification', $event);
	}
}