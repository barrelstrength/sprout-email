<?php
namespace Craft;

class SproutEmail_DefaultMailer extends SproutEmailBaseMailer implements SproutEmailNotificationEmailSenderInterface
{
	/**
	 * @var
	 */
	protected $lists;

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'defaultmailer';
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return 'Sprout Email';
	}

	/**
	 * @return null|string
	 */
	public function getDescription()
	{
		return Craft::t('Smart transactional email, easy recipient management, and advanced third party integrations.');
	}

	/**
	 * @return bool
	 */
	public function hasCpSection()
	{
		return true;
	}

	/**
	 * @return array
	 */
	public function defineSettings()
	{
		return array(
			'fromName'     => array(AttributeType::String, 'required' => true),
			'fromEmail'    => array(AttributeType::Email, 'required' => true),
			'replyToEmail' => array(AttributeType::Email, 'required' => false)
		);
	}

	public function isSettingBuiltIn()
	{
		return true;
	}
	
	/**
	 * @param array $settings
	 *
	 * @return \Twig_Markup
	 */
	public function getSettingsHtml(array $settings = array())
	{
		$settings = isset($settings['settings']) ? $settings['settings'] : $this->getSettings();

		$html = craft()->templates->render('sproutemail/settings/mailers/sproutemail/settings', array(
			'settings' => $settings
		));

		return TemplateHelper::getRaw($html);
	}

	/**
	 * @param SproutEmail_NotificationEmailModel    $notificationEmail
	 * @param BaseModel|BaseElementModel|array|null $object
	 *
	 * @param bool                                  $useMockData
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

		$template = $notificationEmail->template;

		$email = sproutEmail()->renderEmailTemplates($email, $template, $notificationEmail, $object);

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
	 * @param SproutEmail_CampaignTypeModel  $campaignType
	 *
	 * @return SproutEmail_ResponseModel
	 */
	public function sendCampaignEmail(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignTypeModel $campaignType)
	{
		$lists = array();

		try
		{
			$response = array();

			$params = array(
				'email'    => $campaignEmail,
				'campaign' => $campaignType,

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

			$recipients = craft()->request->getPost('recipients');

			if ($recipients == null)
			{
				throw new Exception(Craft::t('Empty recipients.'));
			}

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
					$email->body         = sproutEmail()->renderSiteTemplateIfExists($campaignType->template . '.txt', $params);
					$email->htmlBody     = sproutEmail()->renderSiteTemplateIfExists($campaignType->template, $params);

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

			return SproutEmail_ResponseModel::createModalResponse(
				'sproutemail/_modals/sendEmailConfirmation',
				array(
					'email'         => $campaignEmail,
					'campaign'      => $campaignType,
					'emailModel'    => $response['emailModel'],
					'recipentLists' => $lists,
					'message'       => Craft::t('Campaign sent successfully to email.'),
				)
			);
		}
		catch (\Exception $e)
		{
			sproutEmail()->error($e->getMessage());

			return SproutEmail_ResponseModel::createErrorModalResponse(
				'sproutemail/_modals/sendEmailConfirmation',
				array(
					'email'    => $campaignEmail,
					'campaign' => $campaignType,
					'message'  => Craft::t($e->getMessage()),
				)
			);
		}
	}

	/**
	 * @param SproutEmail_CampaignEmailModel $campaignEmail
	 * @param SproutEmail_CampaignTypeModel  $campaignType
	 *
	 * @return string
	 */
	public function getPrepareModalHtml(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignTypeModel $campaignType)
	{
		if (!empty($campaignEmail->recipients))
		{
			$recipients = $campaignEmail->recipients;
		}

		if (empty($recipients))
		{
			$recipients = craft()->userSession->getUser()->email;
		}

		$errors = array();

		$errors = $this->getErrors($campaignEmail, $campaignType, $errors);

		return craft()->templates->render('sproutemail/_modals/sendEmailPrepare', array(
			'campaignEmail' => $campaignEmail,
			'campaignType'  => $campaignType,
			'recipients'    => $recipients,
			'errors'        => $errors
		));
	}

	/**
	 * Get all supported Lists. Requires Sprout Lists.
	 *
	 * @return array()|null
	 */
	public function getLists()
	{
		if ($this->lists === null)
		{
			$sproutLists = craft()->plugins->getPlugin('sproutLists');

			$this->lists = $sproutLists ? sproutLists()->lists->getLists() : array();
		}

		return $this->lists;
	}

	/**
	 * Get the HTML for our List Settings on the Notification Email edit page
	 *
	 * @param array $values
	 *
	 * @return string
	 */
	public function getListsHtml($values = array())
	{
		$selected = array();
		$options  = array();
		$lists    = $this->getLists();

		if (count($lists))
		{
			foreach ($lists as $list)
			{
				$options[] = array(
					'label' => $list->name . ' (' . $list->total . ')',
					'value' => $list->id
				);
			}
		}
		else
		{
			// Do not display lists if sprout plugin is disabled
			return '';
		}

		$listIds = isset($values['listIds']) ? $values['listIds'] : null;

		if (is_array($listIds) && count($listIds))
		{
			foreach ($listIds as $key => $listId)
			{
				$selected[] = $listId;
			}
		}

		return craft()->templates->render('sproutemail/_integrations/mailer/defaultmailer/lists', array(
			'options' => $options,
			'values'  => $selected,
		));
	}

	public function hasInlineRecipients()
	{
		return true;
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
		// @todo - clarify what entryRecipents and $dynamicRecipients are
		$entryRecipients   = $this->getRecipientsFromCampaignEmailModel($email, $object);
		$dynamicRecipients = sproutEmail()->notificationEmails->getDynamicRecipientsFromElement($object);

		$recipients = array_merge(
			$entryRecipients,
			$dynamicRecipients
		);

		if (craft()->plugins->getPlugin('sproutlists') != null)
		{
			$listType = sproutLists()->lists->getListType('subscriber');

			$sproutListsRecipients = $listType->getSubscribers(array(
				'ids' =>  $email->listSettings['listIds']
			));

			$sproutListsRecipients = SproutEmail_SimpleRecipientModel::populateModels($sproutListsRecipients);

			$recipients = array_merge($recipients, $sproutListsRecipients);
		}

		return $recipients;
	}

	/**
	 * @param SproutEmail_CampaignEmailModel $campaignEmail
	 * @param mixed                          $element
	 *
	 * @return array
	 */
	public function getRecipientsFromCampaignEmailModel($campaignEmail, $element)
	{
		$recipients         = array();
		$onTheFlyRecipients = $campaignEmail->getRecipients($element);

		if (is_string($onTheFlyRecipients))
		{
			$onTheFlyRecipients = explode(",", $onTheFlyRecipients);
		}

		if (count($onTheFlyRecipients))
		{
			foreach ($onTheFlyRecipients as $index => $recipient)
			{
				$recipients[$index] = SproutEmail_SimpleRecipientModel::create(
					array(
						'firstName' => '',
						'lastName'  => '',
						'email'     => $recipient
					)
				);
			}
		}

		return $recipients;
	}

	/**
	 * @param SproutEmail_CampaignEmailModel $campaignEmail
	 * @param SproutEmail_CampaignTypeModel  $campaignType
	 * @param                                $errors
	 *
	 * @return array
	 */
	public function getErrors(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignTypeModel $campaignType, $errors)
	{
		$notificationEditUrl         = UrlHelper::getCpUrl('sproutemail/campaigns/edit/' . $campaignEmail->id);
		$notificationEditSettingsUrl = UrlHelper::getCpUrl('sproutemail/settings/notifications/edit/' . $campaignType->id);

		if (empty($campaignType->template))
		{
			$errors[] = Craft::t('Email Template setting is blank. <a href="{url}">Edit Settings</a>.', array(
				'url' => $notificationEditSettingsUrl
			));
		}

		return $errors;
	}
}
