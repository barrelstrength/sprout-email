<?php
namespace Craft;

/**
 * Enables you to send your campaigns using Mail Chimp
 *
 * Class SproutEmailMailchimpMailer
 *
 * @package Craft
 */
class SproutEmail_MailchimpMailer extends SproutEmailBaseMailer
{
	/**
	 * @var SproutEmailMailchimpService
	 */
	protected $service;

	/**
	 * @throws \Exception
	 * @return SproutEmailMailchimpService
	 */
	public function getService()
	{
		if (is_null($this->service))
		{
			$this->service = Craft::app()->getComponent('sproutEmail_mailchimp');

			$this->service->setSettings($this->getSettings());

			try
			{
				$client = new \Mailchimp($this->getSettings()->getAttribute('apiKey'), array('ssl_verifypeer' => false));

				$this->service->setClient($client);
			}
			catch (\Exception $e)
			{
				throw $e;
			}
		}

		return $this->service;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return 'MailChimp';
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'mailchimp';
	}

	/**
	 * @return string
	 */
	public function getDescription()
	{
		return Craft::t('Send your email campaigns via MailChimp.');
	}

	/**
	 * @param array $context
	 *
	 * @return string
	 */
	public function getSettingsHtml(array $context = array())
	{
		$context['settings'] = $this->getSettings();

		return craft()->templates->render('sproutemail/settings/_mailers/mailchimp/settings', $context);
	}

	/**
	 * @param SproutEmail_EntryModel    $entry
	 * @param SproutEmail_CampaignModel $campaign
	 *
	 * @return string
	 */
	public function getPrepareModalHtml(SproutEmail_EntryModel $entry, SproutEmail_CampaignModel $campaign)
	{
		if (strpos($entry->replyToEmail, '{') !== false)
		{
			$entry->replyToEmail = $entry->fromEmail;
		}

		// Create an array of all recipient list titles
		$lists = sproutEmail()->entries->getRecipientListsByEntryId($entry->id);

		$recipientLists = array();

		if (is_array($lists) && count($lists))
		{
			foreach ($lists as $list)
			{
				$current = $this->getService()->getRecipientListById($list->list);

				array_push($recipientLists, $current);
			}
		}

		return craft()->templates->render(
			'sproutemail/settings/_mailers/mailchimp/prepare',
			array(
				'entry'    => $entry,
				'lists'    => $recipientLists,
				'mailer'   => $this,
				'campaign' => $campaign
			)
		);
	}

	/**
	 * Renders the recipient list UI for this mailer
	 *
	 * @param SproutEmail_EntryModel[]|null $values
	 *
	 * @return string Rendered HTML content
	 */
	public function getRecipientListsHtml(array $values = null)
	{
		$lists = $this->getRecipientLists();
		$options = array();
		$selected = array();

		if ($lists == false)
		{
			return craft()->templates->render('sproutemail/settings/_mailers/mailchimp/lists/sslerror');
		}

		if (!count($lists))
		{
			return craft()->templates->render('sproutemail/settings/_mailers/mailchimp/lists/norecipientlists');
		}

		if (count($lists))
		{
			foreach ($lists as $list)
			{
				if (isset($list['id']) && isset($list['name']))
				{
					$length = 0;

					if ($lists = $this->getService()->getListStatsById($list['id']))
					{
						$length = number_format($lists['member_count']);
					}

					$listUrl = "https://us7.admin.mailchimp.com/lists/members/?id=" . $list['web_id'];

					// @todo Provide a way for info to become a Craft tooltip in the UI
					$options[] = array(
						'label' => sprintf('<a target="_blank" href="%s">%s (%s)</a>', $listUrl, $list['name'], $length),
						'value' => $list['id']
					);
				}
			}
		}

		if (is_array($values) && count($values))
		{
			foreach ($values as $value)
			{
				$selected[] = $value->list;
			}
		}

		return craft()->templates->renderMacro(
			'_includes/forms', 'checkboxGroup', array(
				array(
					'id'      => 'recipientLists',
					'name'    => 'recipient[recipientLists]',
					'options' => $options,
					'values'  => $selected,
				)
			)
		);
	}

	/**
	 * @param $id
	 *
	 * @return mixed
	 */
	public function getRecipientListById($id)
	{
		return $this->getService()->getRecipientListById($id);
	}

	/**
	 * @return array
	 */
	public function getRecipientLists()
	{
		return $this->getService()->getRecipientLists();
	}

	/**
	 * @param SproutEmail_EntryModel    $entry
	 * @param SproutEmail_CampaignModel $campaign
	 *
	 * @return array|SproutEmail_EntryModel
	 */
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
	 * @return array|void
	 */
	public function exportEntry(SproutEmail_EntryModel $entry, SproutEmail_CampaignModel $campaign)
	{
		$sentCampaignIds = array();
		$response = new SproutEmail_ResponseModel();

		try
		{
			$sentCampaign = $this->getService()->export($entry, $campaign);

			$sentCampaignIds = $sentCampaign['ids'];

			$response->emailModel = $sentCampaign['emailModel'];

			$response->success = true;
			$response->message = Craft::t('Campaign successfully sent to {count} recipient lists.', array('count' => count($sentCampaignIds)));
		}
		catch (\Exception $e)
		{
			$response->success = false;
			$response->message = $e->getMessage();

			sproutEmail()->error($e->getMessage());
		}

		$response->content = craft()->templates->render(
			'sproutemail/settings/_mailers/mailchimp/export',
			array(
				'entry'       => $entry,
				'campaign'    => $campaign,
				'mailer'      => $this,
				'success'     => $response->success,
				'message'     => $response->message,
				'campaignIds' => $sentCampaignIds
			)
		);

		return $response;
	}

	public function includeModalResources()
	{
		craft()->templates->includeJsResource('sproutemail/js/mailers/mailchimp.js');
	}

	/**
	 * @return array
	 */
	public function defineSettings()
	{
		return array(
			'apiKey'    => array(AttributeType::String, 'required' => true),
			'inlineCss' => array(AttributeType::String, 'default' => true),
		);
	}
}
