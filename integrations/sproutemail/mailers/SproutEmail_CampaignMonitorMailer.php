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

	public function getTitle()
	{
		return 'Campaign Monitor';
	}

	public function getName()
	{
		return 'campaignmonitor';
	}

	public function getDescription()
	{
		return Craft::t('Send your email campaigns via Campaign Monitor.');
	}

	public function defineSettings()
	{
		return array(
			'clientId' => array(AttributeType::String, 'required' => true),
			'apiKey'   => array(AttributeType::String, 'required' => true),
		);
	}

	public function getSettingsHtml(array $context = array())
	{
		$context['settings'] = $this->getSettings();

		$html = craft()->templates->render('sproutemail/settings/mailers/campaignmonitor/settings', $context);

		return TemplateHelper::getRaw($html);
	}

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
			return craft()->templates->render('sproutemail/settings/mailers/campaignmonitor/defaultMailer/norecipientlists');
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

	public function getPostParams(array $extra = array())
	{
		$params = array(
			'api_key' => $this->settings['apiKey']
		);

		return array_merge($params, $extra);
	}

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
	 * @return SproutEmail_ResponseModel
	 * @throws \Exception
	 */
	public function sendCampaignEmail(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignTypeModel $campaignType)
	{
		try
		{
			$result            = $this->getService()->sendCampaignEmail($campaignEmail, $campaignType);
			$createdCampaignId = $result['id'];
		}
		catch (\Exception $e)
		{
			throw $e;
		}

		$response             = new SproutEmail_ResponseModel();
		$response->emailModel = $result['emailModel'];
		$response->success    = true;
		$response->content    = craft()->templates->render(
			'sproutemail/settings/mailers/campaignmonitor/export',
			array(
				'entry'             => $campaignEmail,
				'campaign'          => $campaignType,
				'success'           => true,
				'response'          => $response,
				'createdCampaignId' => $createdCampaignId,
			)
		);

		return $response;
	}

	public function getPrepareModalHtml(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignTypeModel $campaignType)
	{
		// Get entry URLs
		$urls = $this->getService()->getCampaignEmailUrls($campaignEmail->id, $campaignType->template);

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

		return craft()->templates->render('sproutemail/settings/mailers/campaignmonitor/prepare', array(
			'htmlUrl'  => $urls['html'],
			'textUrl'  => ($urls['hasText']) ? stripslashes($urls['text']) : null,
			'lists'    => $recipientLists,
			'entry'    => $campaignEmail,
			'campaign' => $campaignType
		));
	}
}
