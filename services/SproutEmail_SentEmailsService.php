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

	public function logSentEmail($emailModel, $type = '', $info = array())
	{

		$sentModel  = new SproutEmail_SentEmailModel();
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
		$sentModel->uri           = '';
		$sentModel->slug          = '';
		$sentModel->archived      = false;
		$sentModel->localeEnabled = $element->isLocalized();

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

		$campaignEntryId = (isset($record->info['campaignEntryId'])) ? $record->info['campaignEntryId'] : null;
		$mailer = Craft::t('defaultmailer');
		if($campaignEntryId != null)
		{
			$entry = sproutEmail()->entries->getEntryById($campaignEntryId);

			$campaignId = $entry->campaignId;

			$campaign = sproutEmail()->campaigns->getCampaignById($campaignId);

			$mailer = $campaign->mailer;
		}

		return $mailer;
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