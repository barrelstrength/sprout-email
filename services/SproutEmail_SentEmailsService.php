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
		// Prepare some variables
		// -----------------------------------------------------------

		$emailSettings = $event->sender->getSettings();

		$user       = $event->params['user'];
		$emailModel = $event->params['emailModel'];
		$variables  = $event->params['variables'];

		$info     = isset($variables['sproutemail']['info']) ? $variables['sproutemail']['info'] : null;
		$emailKey = isset($variables['emailKey']) ? $variables['emailKey'] : null;

		$craftVersion = 'Craft ' . craft()->getEditionName() . ' ' . craft()->getVersion() . '.' . craft()->getBuild();

		// Prepare our info table settings for Notifications
		// -----------------------------------------------------------

		$infoTable = new SproutEmail_SentEmailInfoTableModel();

		// General Info
		$infoTable->emailType      = isset($info['emailType']) ? $info['emailType'] : '–';
		$infoTable->deliveryType   = isset($info['deliveryType']) ? $info['deliveryType'] : '–';
		$infoTable->deliveryStatus = isset($info['deliveryStatus']) ? $info['deliveryStatus'] : null;
		$infoTable->message        = isset($info['message']) ? $info['message'] : '–';

		// Sender Info
		$infoTable->senderName    = $emailModel->fromName;
		$infoTable->senderEmail   = $emailModel->fromEmail;
		$infoTable->source        = isset($info['source']) ? $info['source'] : '–';
		$infoTable->sourceVersion = isset($info['sourceVersion']) ? $info['sourceVersion'] : '–';
		$infoTable->craftVersion  = $craftVersion;
		$infoTable->ipAddress     = craft()->request->getUserHostAddress();
		$infoTable->userAgent     = craft()->request->getUserAgent();

		// Email Settings
		$infoTable->protocol                = isset($emailSettings['protocol']) ? $emailSettings['protocol'] : '–';
		$infoTable->hostName                = isset($emailSettings['host']) ? $emailSettings['host'] : '–';
		$infoTable->port                    = isset($emailSettings['port']) ? $emailSettings['port'] : '–';
		$infoTable->smtpSecureTransportType = isset($emailSettings['smtpSecureTransportType']) ? $emailSettings['smtpSecureTransportType'] : '–';
		$infoTable->timeout                 = isset($emailSettings['timeout']) ? $emailSettings['timeout'] : '–';

		// Override some settings if this is an email sent by Craft
		// -----------------------------------------------------------

		if ($emailKey != null)
		{
			$emailModel->toEmail = $user->email;

			$infoTable->emailType     = Craft::t('Craft CMS Email');
			$infoTable->source        = 'Craft CMS';
			$infoTable->sourceVersion = $craftVersion;

			$emailModel = $this->renderEmailContentLikeCraft($emailModel, $variables);
		}

		if ($emailKey == 'test_email')
		{
			$infoTable->deliveryType = Craft::t('Test');

			$emailModel->toEmail = $emailSettings['emailAddress'];
		}

		// -----------------------------------------------------------

		$this->saveSentEmail($emailModel, $infoTable);
	}

	/**
	 * @param Event $event
	 */
	public function logSentEmailCampaign(Event $event)
	{
		// Prepare some variables
		// -----------------------------------------------------------

		$emailModel = $event->params['emailModel'];
		$entryModel = $event->params['entryModel'];
		$campaign   = $event->params['campaign'];

		$info          = isset($variables['sproutemail']['info']) ? $variables['sproutemail']['info'] : null;
		$craftVersion  = 'Craft ' . craft()->getEditionName() . ' ' . craft()->getVersion() . '.' . craft()->getBuild();
		$pluginVersion = craft()->plugins->getPlugin('sproutemail')->getVersion();

		// Prepare our info table settings for Campaigns
		// -----------------------------------------------------------

		$infoTable = new SproutEmail_SentEmailInfoTableModel();

		// General Info
		$infoTable->emailType = "Campaign";

		// Sender Info
		$infoTable->senderName    = $emailModel->fromName;
		$infoTable->senderEmail   = $emailModel->fromEmail;
		$infoTable->source        = 'Sprout Email';
		$infoTable->sourceVersion = 'Sprout Email ' . $pluginVersion;
		$infoTable->craftVersion  = $craftVersion;
		$infoTable->ipAddress     = craft()->request->getUserHostAddress();
		$infoTable->userAgent     = craft()->request->getUserAgent();

		// Email Settings
		$infoTable->mailer = ucwords($campaign->mailer);

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
			$subject       = base64_decode($encodedString);
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

		if ($info->deliveryStatus == 'failed')
		{
			$sentEmail->status = 'failed';
		}

		// Remove deliveryStatus as we can determine it from the status in the future
		$info = $info->getAttributes();
		unset($info['deliveryStatus']);
		$sentEmail->info = JsonHelper::encode($info);

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

	public function getInfoRow(SproutEmail_SentEmailModel $sentEmail)
	{
		$tableInfo = $sentEmail->info;

		if (empty($tableInfo))
		{
			return null;
		}

		$infoMap = array(

			// General Info
			'emailType'               => 'Email Type',
			'deliveryType'            => 'Delivery Type',
			'deliveryStatus'          => 'Delivery Status',
			'message'                 => 'Message',

			// Sender Info
			'senderName'              => 'Sender Name',
			'senderEmail'             => 'Sender Email',
			'source'                  => 'Source',
			'sourceVersion'           => 'Source Version',
			'craftVersion'            => 'Craft Version',
			'ipAddress'               => 'IP Address',
			'userAgent'               => 'User Agent',

			// Email Settings
			'protocol'                => 'Protocol',
			'hostName'                => 'Host Name',
			'port'                    => 'Port',
			'smtpSecureTransportType' => 'SMTP Secure Transport Type',
			'timeout'                 => 'Timeout',
		);

		$row     = array();
		$dataRow = array();

		// @todo - refactor
		// Update all this info table stuff to be smarter
		foreach ($infoMap as $infoKey => $infoLabel)
		{
			$attribute = str_replace(' ', '', strtolower($infoKey));

			if (isset($tableInfo[$infoKey]) && $tableInfo[$infoKey] != '')
			{
				$dataRow[] = "data-" . $attribute . "='" . $tableInfo[$infoKey] . "'";
				$row[]     = '{ "label":"' . $infoLabel . '","attribute":"' . $attribute . '" }';
			}

			if ($attribute == 'deliverystatus')
			{
				$dataRow[] = "data-" . $attribute . "='" . $sentEmail->status . "'";
				$row[]     = '{ "label":"' . $infoLabel . '","attribute":"' . $attribute . '" }';
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
	protected function renderEmailContentLikeCraft($emailModel, $variables)
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

		return $emailModel;
	}
}