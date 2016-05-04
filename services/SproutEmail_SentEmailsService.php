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
	 * @param Event $event
	 */
	public function logSentEmail(Event &$event)
	{
		$emailSettings = $event->sender->getSettings();

		$user       = $event->params['user'];
		$emailModel = $event->params['emailModel'];
		$variables  = $event->params['variables'];

		$info     = isset($variables['sproutemail']['info']) ? $variables['sproutemail']['info'] : null;
		$emailKey = isset($variables['emailKey']) ? $variables['emailKey'] : null;

		$craftVersion = 'Craft ' . craft()->getEditionName() . ' ' . craft()->getVersion() . '.' . craft()->getBuild();

		$infoTable = new SproutEmail_SentEmailInfoTableModel();

		$infoTable->source        = isset($info['source']) ? $info['source'] : '–';
		$infoTable->sourceVersion = isset($info['sourceVersion']) ? $info['sourceVersion'] : '–';
		$infoTable->craftVersion  = $craftVersion;
		$infoTable->emailType     = isset($info['emailType']) ? $info['emailType'] : '–';
		$infoTable->testEmail     = (isset($info['testEmail']) && $info['testEmail'] == true) ? 'Yes' : '–';
		$infoTable->senderName    = $emailModel->fromName;
		$infoTable->senderEmail   = $emailModel->fromEmail;
		$infoTable->protocol      = isset($emailSettings['protocol']) ? $emailSettings['protocol'] : '–';
		$infoTable->hostName      = isset($emailSettings['host']) ? $emailSettings['host'] : '–';
		$infoTable->port          = isset($emailSettings['port']) ? $emailSettings['port'] : '–';

		$infoTable->smtpSecureTransportType = isset($emailSettings['smtpSecureTransportType']) ? $emailSettings['smtpSecureTransportType'] : '–';
		$infoTable->timeout                 = isset($emailSettings['timeout']) ? $emailSettings['timeout'] : '–';
		$infoTable->ipAddress               = craft()->request->getUserHostAddress();
		$infoTable->userAgent               = craft()->request->getUserAgent();

		// Override some settings if this is a Craft email
		if ($emailKey != null)
		{
			$emailModel->toEmail = $user->email;

			$infoTable->source        = 'Craft CMS';
			$infoTable->sourceVersion = $craftVersion;
			$infoTable->emailType     = Craft::t('Notification');
			$infoTable->testEmail     = Craft::t('Test Email');

			$this->renderEmailContentLikeCraft($emailModel, $variables);
		}

		if ($emailKey == 'test_email')
		{
			$emailModel->toEmail = $emailSettings['emailAddress'];
		}

		$this->saveSentEmail($emailModel, $infoTable);
	}

	/**
	 * @param Event $event
	 */
	public function logSentEmailCampaign(Event $event)
	{
		$emailModel = $event->params['emailModel'];
		$entryModel = $event->params['entryModel'];
		$campaign   = $event->params['campaign'];

		$info         = isset($variables['sproutemail']['info']) ? $variables['sproutemail']['info'] : null;
		$craftVersion = 'Craft ' . craft()->getEditionName() . ' ' . craft()->getVersion() . '.' . craft()->getBuild();
		$pluginVersion = craft()->plugins->getPlugin('sproutemail')->getVersion();

		$infoTable = new SproutEmail_SentEmailInfoTableModel();

		$infoTable->source        = 'Sprout Email';
		$infoTable->sourceVersion = 'Sprout Email ' . $pluginVersion;
		$infoTable->craftVersion  = $craftVersion;
		$infoTable->mailer        = ucwords($campaign->mailer);
		$infoTable->emailType     = "Campaign";
		$infoTable->testEmail     = null;
		$infoTable->senderName    = $emailModel->fromName;
		$infoTable->senderEmail   = $emailModel->fromEmail;
		$infoTable->ipAddress     = craft()->request->getUserHostAddress();
		$infoTable->userAgent     = craft()->request->getUserAgent();

		$this->saveSentEmail($emailModel, $infoTable);
	}

	/**
	 * Save email snapshot using the Sent Email Element Type
	 *
	 * @param       $emailModel
	 * @param array $info
	 *
	 * @throws \Exception
	 */
	public function saveSentEmail($emailModel, SproutEmail_SentEmailInfoTableModel $info)
	{
		// decode subject if it is encoded
		$isEncoded = preg_match("/=\?UTF-8\?B\?(.*)\?=/", $emailModel->subject, $matches);
		if ($isEncoded)
		{
			$encodedString = $matches[1];
			$subject = base64_decode($encodedString);
		}
		else
		{
			$subject = $emailModel->subject;
		}

		$sentEmail = new SproutEmail_SentEmailModel();

		$sentEmail->title        = $subject;
		$sentEmail->emailSubject = $subject;
		$sentEmail->fromEmail    = $emailModel->fromEmail;
		$sentEmail->fromName     = $emailModel->fromName;
		$sentEmail->toEmail      = $emailModel->toEmail;
		$sentEmail->body         = $emailModel->body;
		$sentEmail->htmlBody     = $emailModel->htmlBody;

		$sentEmail->getContent()->setAttribute('title', $sentEmail->title);

		$sentEmail->info = JsonHelper::encode($info);

		if ($info->emailType == "Sent Error")
		{
			$sentEmail->status = 'failed';
		}

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

	public function getInfoRow($tableInfo)
	{
		if (empty($tableInfo))
		{
			return null;
		}

		$infoMap = array(
			'source'                  => 'Source',
			'sourceVersion'           => 'Source Version',
			'craftVersion'            => 'Craft Version',
			'emailType'               => 'Email Type',
			'testEmail'               => 'Test Email',
			'senderName'              => 'Sender Name',
			'senderEmail'             => 'Sender Email',
			'protocol'                => 'Protocol',
			'hostName'                => 'Host Name',
			'port'                    => 'Port',
			'smtpSecureTransportType' => 'SMTP Secure Transport Type',
			'timeout'                 => 'Timeout',
			'ipAddress'               => 'IP Address',
			'userAgent'               => 'User Agent'
		);

		$row     = array();
		$dataRow = array();

		foreach ($tableInfo as $infoKey => $info)
		{
			if (isset($infoMap[$infoKey]) && $info != '')
			{
				$attribute = str_replace(' ', '', strtolower($infoKey));

				$row[]     = '{ "label":"' . $infoMap[$infoKey] . '","attribute":"' . $attribute . '" }';
				$dataRow[] = "data-" . $attribute . "='" . $info . "'";
			}
		}

		$infoString = "data-inforow='[" . implode(",", $row) . "]'";
		$dataString = implode(' ', $dataRow);
		$string     = $infoString . ' ' . $dataString;

		return TemplateHelper::getRaw($string);
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
		$emailModel->body     = $renderedTextBody;
	}
}