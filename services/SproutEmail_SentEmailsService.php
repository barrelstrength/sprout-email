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

			sproutEmail()->error($e);

			return false;
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
	 * @param Event $event
	 */
	public function logSentEmail(Event &$event)
	{
		$emailSettings = $event->sender->getSettings();

		$emailModel = $event->params['emailModel'];
		$variables = $event->params['variables'];
		$infoTableVariables = isset($variables['sproutEmailSentEmailVariables']) ? $variables['sproutEmailSentEmailVariables'] : null;

		$emailKey = isset($variables['emailKey']) ? $variables['emailKey'] : null;

		$craftVersion = 'Craft '.craft()->getEditionName().' '.craft()->getVersion().'.'.craft()->getBuild();

		$infoTable = array();

		$infoTable['Source'] = isset($infoTableVariables['Source']) ? $infoTableVariables['Source'] : '–';
		$infoTable['Source Version'] = isset($infoTableVariables['Version']) ? $infoTableVariables['Version'] : '–';
		$infoTable['Craft Version'] = $craftVersion;

		$infoTable['Email Type'] = isset($infoTableVariables['Email Type']) ? $infoTableVariables['Email Type'] : '–';
		$infoTable['Test Email'] = isset($infoTableVariables['Test Email']) ? 'Yes' : null;
		$infoTable['Sender Name'] = $emailModel->fromName;
		$infoTable['Sender Email'] = $emailModel->fromEmail;

		$infoTable['Protocol'] = isset($emailSettings['protocol']) ? $emailSettings['protocol'] : '–';
		$infoTable['Host Name'] = isset($emailSettings['host']) ? $emailSettings['host'] : '–';
		$infoTable['Port'] = isset($emailSettings['port']) ? $emailSettings['port'] : '–';
		$infoTable['SMTP Secure Transport Type'] = isset($emailSettings['smtpSecureTransportType']) ? $emailSettings['smtpSecureTransportType'] : '–';
		$infoTable['Timeout'] = isset($emailSettings['timeout']) ? $emailSettings['timeout'] : '–';

		$infoTable['IP Address'] = craft()->request->getUserHostAddress();
		$infoTable['User Agent'] = craft()->request->getUserAgent();

		// Override some settings if this is a Craft email
		if ($emailKey != null)
		{
			$infoTable['Source'] = Craft::t('Craft CMS');
			$infoTable['Version'] = $craftVersion;
			$infoTable['Type'] = Craft::t('Notification');
			$infoTable['Test'] = Craft::t('Test Email');

			$this->renderEmailContentLikeCraft($emailModel, $variables);
		}

		if ($emailKey == 'test_email')
		{
			$emailModel->toEmail = $emailSettings['emailAddress'];
		}

		$this->saveSentEmail($emailModel, $infoTable);
	}

	/**
	 * Render our HTML and Text email templates like Craft does
	 *
	 * Craft provides us the original email model, so we need to process the
	 * HTML and Text body fields again in order to store the email content as
	 * it was sent. Behavior copied from craft()->emails->sendEmail()
	 */
	protected function renderEmailContentLikeCraft(&$emailModel, $variables)
	{
		if ($emailModel->htmlBody)
		{
			$renderedHtmlBody = craft()->templates->renderString($emailModel->htmlBody, $variables);
			$renderedTextBody = craft()->templates->renderString($emailModel->body, $variables);
		}
		else
		{
			$renderedHtmlBody = craft()->templates->renderString(StringHelper::parseMarkdown($emailModel->body), $variables);
			$renderedTextBody = craft()->templates->renderString($emailModel->body, $variables);
		}

		$emailModel->htmlBody = $renderedHtmlBody;
		$emailModel->body = $renderedTextBody;
	}

	/**
	 * @param Event $event
	 */
	public function logSentEmailCampaign(Event $event)
	{
		$emailModel = $event->params['emailModel'];
		$entryModel = $event->params['entryModel'];
		$campaign = $event->params['campaign'];

		$infoTable = array();
		$infoTable['Mailer'] = ucwords($campaign->mailer);
		$infoTable['Email Type'] = "Campaign";
		$infoTable['Sender Name'] = $entryModel->fromName;
		$infoTable['Sender Email'] = $entryModel->fromEmail;

		sproutEmail()->sentEmails->saveSentEmail($emailModel, $infoTable);
	}
}