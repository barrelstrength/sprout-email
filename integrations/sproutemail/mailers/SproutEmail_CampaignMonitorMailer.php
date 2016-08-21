<?php
namespace Craft;

class SproutEmail_CampaignMonitorMailer extends SproutEmailBaseMailer implements SproutEmailCampaignEmailSenderInterface
{
	/**
	 * @var SproutEmailCampaignMonitorService
	 */
	protected $service;

	/**
	 * @return SproutEmailCampaignMonitorService
	 */
	public function getService()
	{
		if (null === $this->service)
		{
			$this->service = Craft::app()->getComponent('sproutEmail_campaignMonitor');

			$this->service->setSettings($this->getSettings());
		}

		return $this->service;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'campaignmonitor';
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return 'Campaign Monitor';
	}

	/**
	 * @return null|string
	 */
	public function getDescription()
	{
		return Craft::t('Send your email campaigns via Campaign Monitor.');
	}

	/**
	 * @return array
	 */
	public function defineSettings()
	{
		return array(
			'clientId' => array(AttributeType::String, 'required' => true),
			'apiKey'   => array(AttributeType::String, 'required' => true),
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

		$html = craft()->templates->render('sproutemail/settings/mailers/campaignmonitor/settings', array(
			'settings' => $settings
		));

		return TemplateHelper::getRaw($html);
	}

	/**
	 * @return mixed
	 */
	public function getRecipientLists()
	{
		return $this->getService()->getRecipientLists();
	}

	/**
	 * Renders the recipient list UI for this mailer
	 *
	 * @param SproutEmail_CampaignEmailModel []|null $values
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
			return craft()->templates->render('sproutemail/settings/mailers/campaignmonitor/recipientlists/norecipientlists');
		}

		if (count($lists))
		{
			foreach ($lists as $list)
			{
				$options[] = array(
					'label' => sprintf('%s (%d)', $list->Name, $this->getService()->getListStats($list->ListID)->TotalActiveSubscribers),
					'value' => $list->ListID
				);
			}
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
	 * @param SproutEmail_CampaignEmailModel $campaignEmail
	 *
	 * @return Craft\SproutEmail_MailerService
	 */
	public function prepareRecipientLists(SproutEmail_CampaignEmailModel $campaignEmail)
	{
		$ids   = craft()->request->getPost('recipient.recipientLists');
		$lists = array();

		if ($ids)
		{
			foreach ($ids as $id)
			{
				$model = new SproutEmail_RecipientListRelationsModel();

				$model->setAttribute('emailId', $campaignEmail->id);
				$model->setAttribute('mailer', $this->getId());
				$model->setAttribute('list', $id);

				$lists[] = $model;
			}
		}

		return $lists;
	}

	/**
	 * @param SproutEmail_CampaignEmailModel $campaignEmail
	 * @param SproutEmail_CampaignTypeModel  $campaignType
	 *
	 * @return mixed
	 */
	public function getPrepareModalHtml(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignTypeModel $campaignType)
	{
		// Create an array of all recipient list titles
		$lists          = sproutEmail()->campaignEmails->getRecipientListsByEmailId($campaignEmail->id);
		$recipientLists = array();

		if (is_array($lists) && count($lists))
		{
			foreach ($lists as $list)
			{
				array_push($recipientLists, $this->getService()->getDetails($list->list)->Title);
			}
		}

		return craft()->templates->render('sproutemail/settings/mailers/campaignmonitor/sendEmailPrepare', array(
			'campaignEmail'  => $campaignEmail,
			'campaignType'   => $campaignType,
			'recipientLists' => $recipientLists
		));
	}

	/**
	 * @param SproutEmail_CampaignEmailModel $campaignEmail
	 * @param SproutEmail_CampaignTypeModel  $campaignType
	 *
	 * @return SproutEmail_ResponseModel
	 * @throws \Exception
	 */
	public function sendCampaignEmail(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignTypeModel $campaignType)
	{
		try
		{
			$result = $this->getService()->sendCampaignEmail($campaignEmail, $campaignType);
		}
		catch (\Exception $e)
		{
			throw $e;
		}

		$response             = new SproutEmail_ResponseModel();
		$response->emailModel = $result['emailModel'];
		$response->success    = true;
		$response->content    = craft()->templates->render(
			'sproutemail/settings/mailers/campaignmonitor/sendEmailConfirmation',
			array(
				'success'  => true,
				'response' => $response
			)
		);

		return $response;
	}

	/**
	 * @todo - confirm if this is in use
	 *
	 * @param array $extra
	 *
	 * @return array
	 */
	public function getPostParams(array $extra = array())
	{
		$params = array(
			'api_key' => $this->settings['apiKey']
		);

		return array_merge($params, $extra);
	}
}
