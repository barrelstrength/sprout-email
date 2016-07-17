<?php
namespace Craft;

/**
 * Class SproutEmailMailchimpService
 *
 * @package Craft
 */
class SproutEmail_MailchimpService extends BaseApplicationComponent
{
	/**
	 * @var Model
	 */
	protected $settings;

	/**
	 * @var \Mailchimp
	 */
	protected $client;

	/**
	 * Loads the mail chimp library and associated dependencies
	 */
	public function init()
	{
		require_once dirname(__FILE__) . '/../vendor/autoload.php';
	}

	/**
	 * @param Model $settings
	 */
	public function setSettings($settings)
	{
		$this->settings = $settings;
	}

	/**
	 * @param \Mailchimp $client
	 */
	public function setClient(\Mailchimp $client)
	{
		$this->client = $client;
	}

	/**
	 * @return array|null
	 */
	public function getRecipientLists()
	{
		try
		{
			$lists = $this->client->lists->getList();

			if (isset($lists['data']))
			{
				return $lists['data'];
			}
		}
		catch (\Exception $e)
		{
			if ($e->getMessage() == 'API call to lists/list failed: SSL certificate problem: unable to get local issuer certificate')
			{
				return false;
			}
		}
	}

	/**
	 * @param $id
	 *
	 * @throws \Exception
	 * @return array|null
	 */
	public function getRecipientListById($id)
	{
		$params = array('list_id' => $id);

		try
		{
			$lists = $this->client->lists->getList($params);

			if (isset($lists['data']) && ($list = array_shift($lists['data'])))
			{
				return $list;
			}
		}
		catch (\Exception $e)
		{
			throw $e;
		}
	}

	public function getListStatsById($id)
	{
		$params = array('list_id' => $id);

		try
		{
			$lists = $this->client->lists->getList($params);

			$stats = $lists['data'][0]['stats'];

			return $stats;
		}
		catch (\Exception $e)
		{
			throw $e;
		}
	}

	/**
	 * @param $id
	 *
	 * @throws \Exception
	 * @return array|null
	 */
	public function getRecipientsByListId($id)
	{
		try
		{
			$members = $this->client->lists->members($id);

			if (isset($members['data']))
			{
				return $members['data'];
			}
		}
		catch (\Exception $e)
		{
			throw $e;
		}
	}

	public function previewEntry(SproutEmail_CampaignEmailModel $entry, SproutEmail_CampaignModel $campaign)
	{
		$type = craft()->request->getPost('contentType', 'html');
		$ext = strtolower($type) == 'text' ? '.txt' : null;
		$params = array('entry' => $entry, 'campaign' => $campaign);
		$body = sproutEmail()->renderSiteTemplateIfExists($campaign->template . $ext, $params);

		return array('content' => TemplateHelper::getRaw($body));
	}

	/**
	 * @param SproutEmail_CampaignEmailModel    $entry
	 * @param SproutEmail_CampaignModel $campaign
	 * @param bool                      $sendOnExport
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function export($entry, $campaign, $sendOnExport = true)
	{
		$lists = sproutEmail()->campaignEmails->getRecipientListsByEntryId($entry->id);
		$campaignIds = array();

		if ($lists && count($lists))
		{
			$type = 'regular';
			$options = array(
				'title'      => $entry->title,
				'subject'    => $entry->title,
				'from_name'  => $entry->fromName,
				'from_email' => $entry->fromEmail,
				'tracking'   => array(
					'opens'       => true,
					'html_clicks' => true,
					'text_clicks' => false
				),
			);

			$params = array(
				'email'     => $entry,
				'campaign'  => $campaign,
				'recipient' => array(
					'firstName' => 'First',
					'lastName'  => 'Last',
					'email'     => 'user@domain.com'
				),

				// @deprecate - in favor of `email` in v3
				'entry'     => $entry
			);

			$content = array(
				'html' => sproutEmail()->renderSiteTemplateIfExists($campaign->template, $params),
				'text' => sproutEmail()->renderSiteTemplateIfExists($campaign->template . '.txt', $params),
			);

			foreach ($lists as $list)
			{
				$options['list_id'] = $list->list;

				if ($this->settings->inlineCss)
				{
					$options['inline_css'] = true;
				}

				try
				{
					$campaign = $this->client->campaigns->create($type, $options, $content);
					$campaignIds[] = $campaign['id'];

					sproutEmail()->info($campaign);
				}
				catch (\Exception $e)
				{
					throw $e;
				}
			}
		}

		if (count($campaignIds) && $sendOnExport)
		{
			foreach ($campaignIds as $mailchimpCampaignId)
			{
				try
				{
					$this->send($mailchimpCampaignId);
				}
				catch (\Exception $e)
				{
					throw $e;
				}
			}
		}

		$recipientLists = array();
		$toEmails = array();
		if (is_array($lists) && count($lists))
		{
			foreach ($lists as $list)
			{
				$current = $this->getRecipientListById($list->list);

				array_push($recipientLists, $current);
			}
		}

		if (!empty($recipientLists))
		{
			foreach ($recipientLists as $recipientList)
			{
				$toEmails[] = $recipientList['name'] . " (" . $recipientList['stats']['member_count'] . ")";
			}
		}

		$email = new EmailModel();

		$email->subject = $entry->title;
		$email->fromName = $entry->fromName;
		$email->fromEmail = $entry->fromEmail;
		$email->body = $content['text'];
		$email->htmlBody = $content['html'];

		if (!empty($toEmails))
		{
			$email->toEmail = implode(', ', $toEmails);
		}

		return array('ids' => $campaignIds, 'emailModel' => $email);
	}

	/**
	 * Sends a previously created/exported campaign via its mail chimp campaign id
	 *
	 * @param string $mailchimpCampaignId
	 *
	 * @throws \Exception
	 * @return true
	 */
	public function send($mailchimpCampaignId)
	{
		try
		{
			$this->client->campaigns->send($mailchimpCampaignId);

			return true;
		}
		catch (\Exception $e)
		{
			throw $e;
		}
	}
}
