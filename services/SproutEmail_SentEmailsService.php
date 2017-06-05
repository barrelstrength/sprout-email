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
		$emailKey   = isset($variables['emailKey']) ? $variables['emailKey'] : null;

		// If we have info set, grab the custom info that's already prepared
		// If we don't have info, we probably have an email sent by Craft so
		// we can continue with our generic info table model
		$infoTable = isset($variables['info']) ? $variables['info'] : new SproutEmail_SentEmailInfoTableModel();

		// Prepare our info table settings for Notifications
		// -----------------------------------------------------------

		// Sender Info
		$infoTable->senderName  = $emailModel->fromName;
		$infoTable->senderEmail = $emailModel->fromEmail;
		$infoTable->ipAddress   = craft()->request->getUserHostAddress();
		$infoTable->userAgent   = craft()->request->getUserAgent();

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
			$emailModel->toEmail = ($emailKey == 'test_email') ? $emailSettings['emailAddress'] : $user->email;

			$infoTable = $this->updateInfoTableWithCraftInfo($emailKey, $infoTable);

			$emailModel = $this->renderEmailContentLikeCraft($emailModel, $variables);
		}

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
		$campaign   = $event->params['campaign'];

		// If we have info set, grab the custom info that's already prepared
		// If we don't have info, we probably have an email sent by Craft so
		// we can continue with our generic info table model
		$infoTable = isset($variables['info']) ? $variables['info'] : new SproutEmail_SentEmailInfoTableModel();

		// Prepare our info table settings for Campaigns
		// -----------------------------------------------------------

		// General Info
		$infoTable->emailType = "Campaign";

		// Sender Info
		$infoTable->senderName  = $emailModel->fromName;
		$infoTable->senderEmail = $emailModel->fromEmail;

		$infoTable->ipAddress = craft()->request->getUserHostAddress();
		$infoTable->userAgent = craft()->request->getUserAgent();

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
	public function saveSentEmail($emailModel, SproutEmail_SentEmailInfoTableModel $infoTable)
	{
		// Make sure we should be saving Sent Emails
		// -----------------------------------------------------------
		$settings = craft()->plugins->getPlugin('sproutemail')->getSettings();

		if (!$settings->enableSentEmails)
		{
			return false;
		}

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

		if ($infoTable->deliveryStatus == 'failed')
		{
			$sentEmail->status = 'failed';
		}

		// Remove deliveryStatus as we can determine it from the status in the future
		$infoTable = $infoTable->getAttributes();
		unset($infoTable['deliveryStatus']);
		$sentEmail->info = JsonHelper::encode($infoTable);

		// @todo Craft 3 - why do we need to set this to blank?
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

	/**
	 * Create a SproutEmail_SentEmailInfoTableModel with the Craft and plugin info values
	 *
	 * @param       $pluginHandle
	 * @param array $values
	 *
	 * @return BaseModel
	 */
	public function createInfoTableModel($pluginHandle, $values = array())
	{
		$infoTable = SproutEmail_SentEmailInfoTableModel::populateModel($values);

		$plugin = craft()->plugins->getPlugin($pluginHandle);

		$infoTable->source        = $plugin->getName();
		$infoTable->sourceVersion = $plugin->getName() . ' ' . $plugin->getVersion();

		$craftVersion = $this->_getCraftVersion();

		$infoTable->craftVersion = $craftVersion;

		return $infoTable;
	}

	/**
	 * Update the SproutEmail_SentEmailInfoTableModel based on the emailKey
	 *
	 * @param $emailKey
	 * @param $infoTable
	 */
	public function updateInfoTableWithCraftInfo($emailKey, $infoTable)
	{
		$craftVersion = $this->_getCraftVersion();

		$infoTable->emailType     = Craft::t('Craft CMS Email');
		$infoTable->source        = 'Craft CMS';
		$infoTable->sourceVersion = $craftVersion;
		$infoTable->craftVersion  = $craftVersion;

		if ($emailKey == 'test_email')
		{
			$infoTable->deliveryType = Craft::t('Test');
		}

		return $infoTable;
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

	private function _getCraftVersion()
	{
		$version      = craft()->getVersion();
		$craftVersion = '';

		if (version_compare($version, '2.6.2951', '>='))
		{
			$craftVersion = 'Craft ' . craft()->getEditionName() . ' ' . $version;
		}
		else
		{
			$craftVersion = 'Craft ' . craft()->getEditionName() . ' ' . craft()->getVersion() . '.' . craft()->getBuild();
		}

		return $craftVersion;
	}
}