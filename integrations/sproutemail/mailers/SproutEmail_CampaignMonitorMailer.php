<?php
namespace Craft;

class SproutEmail_CampaignMonitorMailer extends SproutEmailBaseMailer
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

		$html = craft()->templates->render('sproutemail/settings/_mailers/campaignmonitor/settings', $context);

		return TemplateHelper::getRaw($html);
	}

	public function getRecipientLists()
	{
		return $this->getService()->getRecipientLists();
	}

	/**
	 * Renders the recipient list UI for this mailer
	 *
	 * @param SproutEmail_EntryModel []|null $values
	 *
	 * @return string|\Twig_Markup
	 */
	public function getRecipientListsHtml(array $values = null)
	{
		$lists = $this->getRecipientLists();
		$options = array();
		$selected = array();

		if (!count($lists))
		{
			return craft()->templates->render('sproutemail/settings/_mailers/campaignmonitor/recipientlists/norecipientlists');
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

	public function prepareRecipientLists(SproutEmail_EntryModel $entry, SproutEmail_CampaignModel $campaign)
	{
		$ids = craft()->request->getPost('recipient.recipientLists');
		$lists = array();

		if ($ids)
		{
			foreach ($ids as $id)
			{
				$model = new SproutEmail_EntryRecipientListModel();

				$model->setAttribute('entryId', $entry->id);
				$model->setAttribute('mailer', $this->getId());
				$model->setAttribute('list', $id);
				$model->setAttribute('type', $campaign->type);

				$lists[] = $model;
			}
		}

		return $lists;
	}

	/**
	 * @param SproutEmail_EntryModel    $entry
	 * @param SproutEmail_CampaignModel $campaign
	 *
	 * @throws \Exception
	 * @return SproutEmail_ResponseModel
	 */
	public function exportEntry(SproutEmail_EntryModel $entry, SproutEmail_CampaignModel $campaign)
	{
		try
		{
			$result = $this->getService()->exportEntry($entry, $campaign);
			$createdCampaignId = $result['id'];
		}
		catch (\Exception $e)
		{
			throw $e;
		}

		$response = new SproutEmail_ResponseModel();
		$response->emailModel = $result['emailModel'];
		$response->success = true;
		$response->content = craft()->templates->render(
			'sproutemail/settings/_mailers/campaignmonitor/export',
			array(
				'entry'             => $entry,
				'campaign'          => $campaign,
				'success'           => true,
				'response'          => $response,
				'createdCampaignId' => $createdCampaignId,
			)
		);

		return $response;
	}

	public function getPrepareModalHtml(SproutEmail_EntryModel $entry, SproutEmail_CampaignModel $campaign)
	{
		// Get entry URLs
		$urls = $this->getService()->getEntryUrls($entry->id, $campaign->template);

		// Create an array of all recipient list titles
		$lists = sproutEmail()->entries->getRecipientListsByEntryId($entry->id);
		$recipientLists = array();

		if (is_array($lists) && count($lists))
		{
			foreach ($lists as $list)
			{
				array_push($recipientLists, $this->getService()->getDetails($list->list)->Title);
			}
		}

		// Set up an array of details to output to the user
		$details = array(
			'htmlUrl'  => $urls['html'],
			'lists'    => $recipientLists,
			'entry'    => $entry,
			'campaign' => $campaign
		);

		// Append textUrl to $details if a text template exists
		if ($urls['hasText'])
		{
			$details['textUrl'] = stripslashes($urls['text']);
		}

		return craft()->templates->render('sproutemail/settings/_mailers/campaignmonitor/prepare', $details);
	}

	public function previewEntry(SproutEmail_EntryModel $entry, SproutEmail_CampaignModel $campaign)
	{
		try
		{
			return $this->getService()->previewEntry($entry, $campaign);
		}
		catch (\Exception $e)
		{
			throw $e;
		}
	}

	public function getPreviewModalHtml(SproutEmail_EntryModel $entry, SproutEmail_CampaignModel $campaign)
	{
		return $this->getService()->previewEntry($entry, $campaign);
	}

	public function getActionForPreview()
	{
		return 'sproutEmail/entry/preview';
	}

	public function includeModalResources()
	{
		craft()->templates->includeJsResource('sproutemail/js/mailers/campaignmonitor.js');
	}
}
