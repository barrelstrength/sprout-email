<?php
namespace Craft;

class SproutEmail_DefaultMailer extends SproutEmailBaseMailer implements SproutEmailNotificationEmailSenderInterface
{
	/**
	 * @var SproutEmail_DefaultMailerService
	 */
	protected $service;

	/**
	 * @return SproutEmail_DefaultMailerService
	 */
	public function getService()
	{
		if (is_null($this->service))
		{
			$this->service = Craft::app()->getComponent('sproutEmail_defaultMailer');
		}

		return $this->service;
	}

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
			'fromName'           => array(AttributeType::String, 'required' => true),
			'fromEmail'          => array(AttributeType::Email, 'required' => true),
			'replyToEmail'       => array(AttributeType::Email, 'required' => false),
			'enableDynamicLists' => array(AttributeType::Bool, 'default' => false),
		);
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
	 * @param SproutEmail_CampaignEmailModel $campaignEmail
	 * @param SproutEmail_CampaignTypeModel  $campaignType
	 *
	 * @return string
	 */
	public function getPrepareModalHtml(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignTypeModel $campaignType)
	{
		// Display the testToEmailAddress if it exists
		$recipients = craft()->config->get('testToEmailAddress');

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
	 * @param SproutEmail_NotificationEmailModel    $notificationEmail
	 * @param BaseModel|BaseElementModel|array|null $object
	 *
	 * @param bool                                  $useMockData
	 *
	 * @return bool
	 */
	public function sendNotificationEmail(SproutEmail_NotificationEmailModel $notificationEmail, $object = null, $useMockData = false)
	{
		return $this->getService()->sendNotificationEmail($notificationEmail, $object, $useMockData);
	}

	/**
	 * @param SproutEmail_CampaignEmailModel $campaignEmail
	 * @param SproutEmail_CampaignTypeModel  $campaignType
	 *
	 * @return array
	 */
	public function sendCampaignEmail(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignTypeModel $campaignType)
	{
		$lists          = sproutEmail()->campaignEmails->getRecipientListsByEmailId($campaignEmail->id);
		$recipientLists = array();

		if (count($lists))
		{
			foreach ($lists as $list)
			{
				$recipientList = sproutEmailDefaultMailer()->getRecipientListById($list->list);

				if ($recipientList)
				{
					$recipientLists[] = $recipientList;
				}
			}
		}

		try
		{
			$response = $this->getService()->sendCampaignEmail($campaignEmail, $campaignType);

			return SproutEmail_ResponseModel::createModalResponse(
				'sproutemail/_modals/sendEmailConfirmation',
				array(
					'email'         => $campaignEmail,
					'campaign'      => $campaignType,
					'emailModel'    => $response['emailModel'],
					'recipentLists' => $recipientLists,
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
