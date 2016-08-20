<?php
namespace Craft;

/**
 * Enables you to send your campaigns using MailChimp
 *
 * Class SproutEmailMailchimpMailer
 *
 * @package Craft
 */
class SproutEmail_MailchimpMailer extends SproutEmailBaseMailer implements SproutEmailCampaignEmailSenderInterface
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
	public function getName()
	{
		return 'mailchimp';
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

		return craft()->templates->render('sproutemail/settings/mailers/mailchimp/settings', $context);
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

	/**
	 * Renders the recipient list UI for this mailer
	 *
	 * @param SproutEmail_CampaignEmailModel[]|null $values
	 *
	 * @return string Rendered HTML content
	 */
	public function getRecipientListsHtml(array $values = null)
	{
		$lists = $this->getRecipientLists();

		$options  = array();
		$selected = array();

		if ($lists === false)
		{
			return craft()->templates->render('sproutemail/settings/mailers/mailchimp/lists/sslerror');
		}

		if (!is_array($lists))
		{
			return $lists;
		}

		if (!count($lists))
		{
			return craft()->templates->render('sproutemail/settings/mailers/mailchimp/lists/norecipientlists');
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
	 * @param SproutEmail_CampaignEmailModel $campaignEmail
	 * @param SproutEmail_CampaignTypeModel  $campaign
	 *
	 * @return array|SproutEmail_CampaignEmailModel
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
	 * @return string
	 */
	public function getPrepareModalHtml(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignTypeModel $campaignType)
	{
		if (strpos($campaignEmail->replyToEmail, '{') !== false)
		{
			$campaignEmail->replyToEmail = $campaignEmail->fromEmail;
		}

		// Create an array of all recipient list titles
		$lists = sproutEmail()->campaignEmails->getRecipientListsByEmailId($campaignEmail->id);

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
			'sproutemail/settings/mailers/mailchimp/prepare',
			array(
				'email'        => $campaignEmail,
				'lists'        => $recipientLists,
				'mailer'       => $this,
				'campaignType' => $campaignType
			)
		);
	}

	/**
	 * @param SproutEmail_CampaignEmailModel $campaignEmail
	 * @param SproutEmail_CampaignTypeModel  $campaignType
	 *
	 * @return array|void
	 */
	public function sendCampaignEmail(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignTypeModel $campaignType)
	{
		$sentCampaignIds = array();
		$response        = new SproutEmail_ResponseModel();

		try
		{
			$sentCampaign = $this->getService()->sendCampaignEmail($campaignEmail, $campaignType);

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
			'sproutemail/settings/mailers/mailchimp/export',
			array(
				'entry'       => $campaignEmail,
				'campaign'    => $campaignType,
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
}
