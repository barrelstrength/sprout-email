<?php

namespace Craft;

/**
 * Class SproutEmail_SentEmails
 * @package Craft
 */

class SproutEmail_SentEmailsService extends BaseApplicationComponent
{

	/**
	 * Stores sent email on Sent Email Element type
	 * @param $sproutEmailEntry
	 * @param $emailModel
	 * @param string $type
	 * @throws Exception
	 * @throws \CDbException
	 * @throws \Exception
	 */

	public function logSentEmail($sproutEmailEntry, $emailModel, $type = '')
	{
		$entryId = $sproutEmailEntry->id;
		$notificationRecord = SproutEmail_NotificationRecord::model()->findByAttributes(array('campaignId' => $sproutEmailEntry->campaignId));

		$notificationId = isset($notificationRecord) ? $notificationRecord->id : null;

		$sentModel  = new SproutEmail_SentEmailModel();
		// validate the element type & throw an exception if it fails
		$element = craft()->elements->getElementType($sentModel->getElementType());
		if (!$element)
		{
			throw new Exception(Craft::t('The {t} element type is not available.',
				array('t' => $model->getElementType())
			));
		}

		// set global Element attributes
		$sentModel->uri           = '';
		$sentModel->slug          = '';
		$sentModel->archived      = false;
		$sentModel->localeEnabled = $element->isLocalized();

		$sentModel->campaignEntryId        = $entryId;
		$sentModel->campaignNotificationId = $notificationId;

		$sentModel->title 				= $emailModel->subject;
		$sentModel->emailSubject 		= $emailModel->subject;
		$sentModel->fromEmail 			= $emailModel->fromEmail;
		$sentModel->fromName  			= $emailModel->fromName;
		$sentModel->toEmail   			= $emailModel->toEmail;
		$sentModel->body      			= $emailModel->body;
		$sentModel->htmlBody  			= $emailModel->htmlBody;
		$sentModel->type    			= $type;

		$sentModel->getContent()->setAttribute('title', $sentModel->title);

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

		try
		{
			if(craft()->elements->saveElement($sentModel))
			{
				$sentRecord = new SproutEmail_SentEmailRecord();

				// set the Records attributes
				$sentRecord->setAttributes($sentModel->getAttributes(), false);
				if($sentRecord->save())
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
	 * Returns sent email mailer
	 *
	 * @param $id Campaign Entry Id
	 * @return string mailer
	 */
	public function getMailerBySentEmailId($id)
	{
		$record = SproutEmail_SentEmailRecord::model()->findByPk($id);

		if($mailer = $record->campaignEntry->campaign->mailer)
		{
			return $mailer;
		}

		return false;
	}

	/**
	 * Event for send notification
	 * @param $vars
	 * @throws \CException
	 */
	public function onSendNotification($vars)
	{
		$event = new Event($this, $vars);
		$this->raiseEvent('onSendNotification', $event);
	}
}