<?php
namespace Craft;

/**
 * Class SproutEmail_DefaultMailerService
 *
 * @package Craft
 */
class SproutEmail_DefaultMailerService extends BaseApplicationComponent
{
	/**
	 * @var Model
	 */
	protected $settings;

	/**
	 * @param SproutEmail_NotificationEmailModel $notificationEmail
	 * @param mixed|null                         $object
	 * @param bool                               $useMockData
	 *
	 * @return bool
	 */
	public function sendNotificationEmail(SproutEmail_NotificationEmailModel $notificationEmail, $object = null, $useMockData = false)
	{
		$email = new EmailModel();

		// Allow disabled emails to be tested
		if (!$notificationEmail->isReady() AND !$useMockData)
		{
			return false;
		}

		$recipients = $this->prepareRecipients($notificationEmail, $object, $useMockData);

		if (empty($recipients))
		{
			sproutEmail()->error(Craft::t('No recipients found.'));
		}

		// Pass this variable for logging sent error
		$emailModel = $email;

		$template = $notificationEmail->template;

		$email = $this->renderEmailTemplates($email, $template, $notificationEmail, $object);

		$templateErrors = sproutEmail()->getError();

		if (empty($templateErrors) && (empty($email->body) OR empty($email->htmlBody)))
		{
			$message = Craft::t('Email Text or HTML template cannot be blank. Check template setting.');

			sproutEmail()->error($message, 'blank-template');
		}

		$processedRecipients = array();

		foreach ($recipients as $recipient)
		{
			$email->toEmail     = sproutEmail()->renderObjectTemplateSafely($recipient->email, $object);
			$email->toFirstName = $recipient->firstName;
			$email->toLastName  = $recipient->lastName;

			if (array_key_exists($email->toEmail, $processedRecipients))
			{
				continue;
			}

			try
			{
				$infoTable = sproutEmail()->sentEmails->createInfoTableModel('sproutemail', array(
					'emailType'    => 'Notification',
					'deliveryType' => ($useMockData ? 'Test' : 'Live')
				));

				$variables = array(
					'email'               => $notificationEmail,
					'renderedEmail'       => $email,
					'object'              => $object,
					'recipients'          => $recipients,
					'processedRecipients' => null,
					'info'                => $infoTable
				);

				if (sproutEmail()->sendEmail($email, $variables))
				{
					$processedRecipients[] = $email->toEmail;
				}
				else
				{
					return false;
				}
			}
			catch (\Exception $e)
			{
				sproutEmail()->error($e->getMessage());
			}
		}

		// Trigger on send notification event
		if (!empty($processedRecipients))
		{
			$variables['processedRecipients'] = $processedRecipients;
		}

		return true;
	}

	/**
	 * @param SproutEmail_CampaignEmailModel $campaignEmail
	 * @param SproutEmail_CampaignTypeModel  $campaign
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function sendCampaignEmail(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignTypeModel $campaign)
	{
		$response = array();

		$params = array(
			'email'    => $campaignEmail,
			'campaign' => $campaign,

			// @deprecate - in favor of `email` in v3
			'entry'    => $campaignEmail
		);

		$email = array(
			'fromEmail' => $campaignEmail->fromEmail,
			'fromName'  => $campaignEmail->fromName,
			'subject'   => $campaignEmail->subjectLine,
		);

		if ($campaignEmail->replyToEmail && filter_var($campaignEmail->replyToEmail, FILTER_VALIDATE_EMAIL))
		{
			$email['replyToEmail'] = $campaignEmail->replyToEmail;
		}

		$recipients = array();

		$recipients = craft()->request->getPost('recipients');

		$result = sproutEmail()->getValidAndInvalidRecipients($recipients);

		$invalidRecipients = $result['invalid'];
		$validRecipients   = $result['valid'];

		if (!empty($invalidRecipients))
		{
			$invalidEmails = implode("<br />", $invalidRecipients);

			throw new Exception(Craft::t("Recipient email addresses do not validate: <br /> {invalidEmails}", array(
				'invalidEmails' => $invalidEmails
			)));
		}

		$recipients = $validRecipients;

		$email = EmailModel::populateModel($email);

		foreach ($recipients as $recipient)
		{
			try
			{
				$params['recipient'] = $recipient;
				$email->body         = sproutEmail()->renderSiteTemplateIfExists($campaign->template . '.txt', $params);
				$email->htmlBody     = sproutEmail()->renderSiteTemplateIfExists($campaign->template, $params);

				$email->setAttribute('toEmail', $recipient->email);
				$email->setAttribute('toFirstName', $recipient->firstName);
				$email->setAttribute('toLastName', $recipient->lastName);

				sproutEmail()->sendEmail($email);
			}
			catch (\Exception $e)
			{
				throw $e;
			}
		}

		$response['emailModel'] = $email;

		return $response;
	}

	/**
	 * @param $email
	 * @param $object
	 * @param $useMockData
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function prepareRecipients($email, $object, $useMockData)
	{
		// Get recipients for test notifications
		if ($useMockData)
		{
			$recipients = craft()->request->getPost('recipients');

			if (empty($recipients))
			{
				return array();
			}

			$recipients = craft()->request->getPost('recipients');

			$result = sproutEmail()->getValidAndInvalidRecipients($recipients);

			$invalidRecipients = $result['invalid'];
			$validRecipients   = $result['valid'];

			if (!empty($invalidRecipients))
			{
				$invalidEmails = implode("<br />", $invalidRecipients);

				throw new Exception(Craft::t("Recipient email addresses do not validate: <br /> {invalidEmails}", array(
					'invalidEmails' => $invalidEmails
				)));
			}

			return $validRecipients;
		}

		// Get recipients for live emails
		$entryRecipients   = $this->getRecipientsFromCampaignEmailModel($email, $object);
		$dynamicRecipients = sproutEmail()->notificationEmails->getDynamicRecipientsFromElement($object);
		$listRecipients    = $this->getListRecipients($email);

		$recipients = array_merge(
			$listRecipients,
			$entryRecipients,
			$dynamicRecipients
		);

		if (craft()->plugins->getPlugin('sproutlists') != null)
		{
			$ids = array($email->id);

			$sproutListsRecipients = sproutLists()->getAllRecipientsByElementIds($ids);

			$recipients = array_merge($recipients, $sproutListsRecipients);

		}

		return $recipients;
	}

	/**
	 * @param $email
	 *
	 * @return array|SproutEmail_DefaultMailerRecipientModel[]
	 */
	protected function getListRecipients($email)
	{
		$listIds = array();

		// Make sure Lists are enabled
		// -----------------------------------------------------------
		$settings = craft()->plugins->getPlugin('sproutemail')->getSettings();

		if (!$settings->enableRecipientLists)
		{
			return $listIds;
		}

		$recipientLists = $this->getRecipientListsByEmailId($email->id);

		if ($recipientLists && count($recipientLists))
		{
			foreach ($recipientLists as $list)
			{
				$listIds[] = $list->id;
			}
		}

		$listRecipients = $this->getRecipientsByRecipientListIds($listIds);

		return $listRecipients;
	}

	/**
	 * @param EmailModel $emailModel
	 * @param            $campaign
	 * @param            $email
	 * @param            $object
	 *
	 * @return bool|EmailModel
	 */
	public function renderEmailTemplates(EmailModel $emailModel, $template = '', $notification, $object)
	{
		// Render Email Entry fields that have dynamic values
		$emailModel->subject   = sproutEmail()->renderObjectTemplateSafely($notification->subjectLine, $object);
		$emailModel->fromName  = sproutEmail()->renderObjectTemplateSafely($notification->fromName, $object);
		$emailModel->fromEmail = sproutEmail()->renderObjectTemplateSafely($notification->fromEmail, $object);
		$emailModel->replyTo   = sproutEmail()->renderObjectTemplateSafely($notification->replyToEmail, $object);

		// Render the email templates
		$emailModel->body     = sproutEmail()->renderSiteTemplateIfExists($template . '.txt', array(
			'email'        => $notification,
			'object'       => $object,

			// @deprecate in v3 in favor of the `email` variable
			'entry'        => $notification,
			'notification' => $notification
		));
		$emailModel->htmlBody = sproutEmail()->renderSiteTemplateIfExists($template, array(
			'email'        => $notification,
			'object'       => $object,

			// @deprecate in v3 in favor of the `email` variable
			'entry'        => $notification,
			'notification' => $notification
		));

		$styleTags = array();

		$htmlBody = $this->addPlaceholderStyleTags($emailModel->htmlBody, $styleTags);

		// Some Twig code in our email fields may need us to decode
		// entities so our email doesn't throw errors when we try to
		// render the field objects. Example: {variable|date("Y/m/d")}
		$emailModel->body = HtmlHelper::decode($emailModel->body);
		$htmlBody         = HtmlHelper::decode($htmlBody);

		// Process the results of the template s once more, to render any dynamic objects used in fields
		$emailModel->body     = sproutEmail()->renderObjectTemplateSafely($emailModel->body, $object);
		$emailModel->htmlBody = sproutEmail()->renderObjectTemplateSafely($htmlBody, $object);

		$emailModel->htmlBody = $this->removePlaceholderStyleTags($emailModel->htmlBody, $styleTags);

		// @todo - update error handling
		$templateError = sproutEmail()->getError('template');

		if (!empty($templateError))
		{
			$emailModel->htmlBody = $templateError;
		}

		return $emailModel;
	}

	public function addPlaceholderStyleTags($htmlBody, &$styleTags)
	{
		// Get the style tag
		preg_match_all("/<style\\b[^>]*>(.*?)<\\/style>/s", $htmlBody, $matches);

		$results = array();

		if (!empty($matches))
		{
			$tags = $matches[0];

			// Temporarily replace with style tags with a random string
			if (!empty($tags))
			{
				$i = 0;
				foreach ($tags as $tag)
				{
					$key = "<!-- %style$i% -->";

					$styleTags[$key] = $tag;

					$htmlBody = str_replace($tag, $key, $htmlBody);

					$i++;
				}
			}
		}

		return $htmlBody;
	}

	// Put back the style tag after object is rendered if style tag is found
	public function removePlaceholderStyleTags($htmlBody, $styleTags)
	{
		if (!empty($styleTags))
		{
			foreach ($styleTags as $key => $tag)
			{
				$htmlBody = str_replace($key, $tag, $htmlBody);
			}
		}

		return $htmlBody;
	}
}
