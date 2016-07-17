<?php
namespace Craft;

class SproutEmail_DefaultMailer extends SproutEmailBaseMailer implements SproutEmailNotificationSenderInterface
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
	 * @param array $context
	 *
	 * @return \Twig_Markup
	 */
	public function getSettingsHtml(array $context = array())
	{
		if (!isset($context['settings']) || $context['settings'] === null)
		{
			$context['settings'] = $this->getSettings();
		}

		$html = craft()->templates->render('sproutemail/settings/_mailers/sproutemail/settings', $context);

		return TemplateHelper::getRaw($html);
	}

	/**
	 * @param $recipientListHandle
	 *
	 * @return SproutEmail_DefaultMailerRecipientModel[]
	 */
	public function getRecipients($recipientListHandle)
	{
		if (($list = $this->getService()->getRecipientListByHandle($recipientListHandle)))
		{
			return SproutEmail_DefaultMailerRecipientModel::populateModels($list->recipients);
		}
	}

	/**
	 * @param $id
	 *
	 * @return SproutEmail_DefaultMailerRecipientListModel|null
	 */
	public function getRecipientListById($id)
	{
		return $this->getService()->getRecipientListById($id);
	}

	/**
	 * @return SproutEmail_DefaultMailerRecipientListModel[]|null
	 */
	public function getRecipientLists()
	{
		return $this->getService()->getRecipientLists($this->getId());
	}

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
	 * @return bool
	 */
	public function hasCpSection()
	{
		return true;
	}

	/**
	 * @param SproutEmail_CampaignEmailModel $campaignEmail
	 * @param SproutEmail_CampaignModel      $campaign
	 *
	 * @return array
	 */
	public function prepareRecipientLists(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignModel $campaign)
	{
		$ids   = craft()->request->getPost('recipient.recipientLists');
		$lists = array();

		if ($ids)
		{
			foreach ($ids as $id)
			{
				$model = new SproutEmail_EntryRecipientListModel();

				$model->setAttribute('emailId', $campaignEmail->id);
				$model->setAttribute('mailer', $this->getId());
				$model->setAttribute('list', $id);
				$model->setAttribute('type', $campaign->type);

				$lists[] = $model;
			}
		}

		return $lists;
	}

	/**
	 * Renders the recipient list UI for this mailer
	 *
	 * @param SproutEmail_CampaignEmailModel[] $values
	 *
	 * @return string|\Twig_Markup
	 */
	public function getRecipientListsHtml(array $values = null)
	{
		$lists    = $this->getRecipientLists();
		$options  = array();
		$selected = array();

		if (!count($lists))
		{
			return craft()->templates->render('sproutemail/settings/_mailers/sproutemail/norecipients');
		}

		foreach ($lists as $list)
		{
			$options[] = array(
				'label' => $list->name,
				'value' => $list->id
			);
		}

		if (is_array($values) && count($values))
		{
			foreach ($values as $value)
			{
				$selected[] = $value->list;
			}
		}

		$html = craft()->templates->renderMacro(
			'_includes/forms', 'checkboxGroup', array(
				array(
					'id'      => 'recipientLists',
					'name'    => 'recipient[recipientLists]',
					'options' => $options,
					'values'  => $selected,
				)
			)
		);

		return TemplateHelper::getRaw($html);
	}

	/**
	 * @param SproutEmail_CampaignModel             $campaign
	 * @param BaseModel|BaseElementModel|array|null $object
	 *
	 * @return bool
	 */
	public function sendNotification($campaign, $object = null)
	{
		return $this->getService()->sendNotification($campaign, $object);
	}

	/**
	 * @param SproutEmail_CampaignEmailModel $campaignEmail
	 * @param SproutEmail_CampaignModel      $campaign
	 *
	 * @return array
	 */
	public function exportEmail(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignModel $campaign)
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
			$response = $this->getService()->exportEmail($campaignEmail, $campaign);

			return SproutEmail_ResponseModel::createModalResponse(
				'sproutemail/_modals/export',
				array(
					'entry'         => $campaignEmail,
					'campaign'      => $campaign,
					'emailModel'    => $response['emailModel'],
					'recipentLists' => $recipientLists,
					'message'       => $campaign->isNotification() ? Craft::t('Notification sent successfully.') : Craft::t('Campaign sent successfully to email ' . $sessionEmail),
				)
			);
		}
		catch (\Exception $e)
		{
			sproutEmail()->error($e->getMessage());

			return SproutEmail_ResponseModel::createErrorModalResponse(
				'sproutemail/_modals/export',
				array(
					'entry'    => $campaignEmail,
					'campaign' => $campaign,
					'message'  => Craft::t($e->getMessage()),
				)
			);
		}
	}

	/**
	 * @param SproutEmail_CampaignEmailModel $campaignEmail
	 * @param SproutEmail_CampaignModel      $campaign
	 *
	 * @return array
	 */
	public function previewCampaignEmail(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignModel $campaign)
	{
		$success = false;

		try
		{
			$this->getService()->exportEmail($campaignEmail, $campaign);

			$success = true;
		}
		catch (\Exception $e)
		{
			sproutEmail()->error($e->getMessage());
		}

		$content = craft()->templates->render(
			'sproutemail/_modals/export',
			array(
				'entry'    => $campaignEmail,
				'campaign' => $campaign,
				'success'  => $success,
			)
		);

		return compact('content');
	}

	/**
	 * @param SproutEmail_CampaignEmailModel $campaignEmail
	 * @param SproutEmail_CampaignModel      $campaign
	 *
	 * @return string
	 */
	public function getPrepareModalHtml(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignModel $campaign)
	{
		// Display the testToEmailAddress if it exists
		$email = craft()->config->get('testToEmailAddress');

		if (empty($email))
		{
			$email = craft()->userSession->getUser()->email;
		}

		$errors = array();

		$errors = $this->getErrors($campaignEmail, $campaign, $errors);

		return craft()->templates->render(
			'sproutemail/_modals/prepare',
			array(
				'entry'     => $campaignEmail,
				'campaign'  => $campaign,
				'recipient' => $email,
				'errors'    => $errors
			)
		);
	}

	/**
	 * @param SproutEmail_CampaignEmailModel $campaignEmail
	 * @param SproutEmail_CampaignModel      $campaign
	 * @param                                $errors
	 *
	 * @return array
	 */
	public function getErrors(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignModel $campaign, $errors)
	{
		$notificationEditUrl         = UrlHelper::getCpUrl('sproutemail/campaigns/edit/' . $campaignEmail->id);
		$notificationEditSettingsUrl = UrlHelper::getCpUrl('sproutemail/settings/notifications/edit/' . $campaign->id);

		if (empty($campaign->template))
		{
			$errors[] = Craft::t('Email Template setting is blank. <a href="{url}">Edit Settings</a>.', array(
				'url' => $notificationEditSettingsUrl
			));
		}

		// @todo - refactor
		// All additional errors are specific to notifications.
		if ($campaign->isNotification())
		{
			$event = sproutEmail()->notificationEmails->getEventByCampaignId($campaign->id);

			if ($event)
			{
				$object = $event->getMockedParams();

				$vars = sproutEmail()->notificationEmails->prepareNotificationTemplateVariables($campaignEmail, $object);

				// @todo - check for text template too
				$template = sproutEmail()->renderSiteTemplateIfExists($campaign->template, $vars);

				if (empty($template))
				{
					$errors[] = Craft::t('{message} <a href="{url}">Edit Settings</a>', array(
						'message' => sproutEmail()->getError('template'),
						'url' => $notificationEditSettingsUrl
					));
				}
			}
			else
			{
				$errors[] = Craft::t('No Event is selected. <a href="{url}">Edit Notification</a>.', array(
					'url' => $notificationEditUrl
				));
			}
		}

		return $errors;
	}
}
