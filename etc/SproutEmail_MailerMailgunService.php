<?php
namespace Craft;

use Mailgun\Connection;
use Mailgun\Mailgun;

class SproutEmail_MailerMailgunService extends BaseApplicationComponent
{
	/**
	 * @var array
	 */
	protected $settings;
	private $export_once;     # Track that we only run the export once.

	public function setSettings(array $settings)
	{
		$this->settings = $settings;
	}

	public function getSubscriberList()
	{
		$lists    = array();
		$client   = new Mailgun($this->settings['apiKey']);
		$result   = $client->get('lists');
		$response = $result->http_response_body;

		foreach ($response->items as $list)
		{
			$lists[$list->address] = '<b>'.$list->name.'</b> ('.$list->members_count.') <span class="info">'.$list->description.'</span>';
		}

		return $lists;
	}

	public function getCampaignList()
	{
		$entries = array();

		// List the campaign options
		$client   = new Mailgun($this->settings['apiKey']);
		$result   = $client->get($this->settings['domain'].'/campaigns');
		$response = $result->http_response_body;

		$entries[] = array(
			'label' => 'No Campaign',
			'value' => ''
		);

		foreach ($response->items as $list)
		{
			$entries[] = array(
				'label' => $list->name,
				'value' => $list->id
			);
		}

		return $entries;
	}

	/**
	 * Exports campaign (no send)
	 *
	 * @param array $campaign
	 * @param array $listIds
	 */
	public function exportEntry($campaign = array(), $listIds = array(), $return = false)
	{
		// We only want to run once! 
		if ($this->export_once == 1)
		{
			return;
		}

		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
		$endpoint = ($protocol.$_SERVER['HTTP_HOST']);
		$client   = new Client($endpoint);

		// send request / get response / grab body of email
		$request     = $client->get('/'.$campaign["htmlTemplate"].'?entryId='.$campaign["entryId"]);
		$response    = $request->send();
		$htmlContent = trim($response->getBody());

		// send request / get response / grab body of text email
		$textRequest  = $client->get('/'.$campaign["textTemplate"].'?entryId='.$campaign["entryId"]);
		$textResponse = $textRequest->send();
		$textContent  = trim($textResponse->getBody());

		// Get the subject of the email from the entry
		// @TODO update this to use the Entry Element
		$entry        = craft()->entries->getEntryById($campaign["entryId"]);
		$emailSubject = $entry->title;

		// Convert encoded quotes
		// http://stackoverflow.com/questions/1262038/how-to-replace-microsoft-encoded-quotes-in-php
		$emailSubject = iconv('UTF-8', 'ASCII//TRANSLIT', $emailSubject);

		// Send to mailgun				
		$recipients = craft()->db->createCommand(
			"SELECT
																								r.type, 
																								r.emailProviderRecipientListId as val 
																							FROM 
																								".DbHelper::addTablePrefix('sproutemail_campaigns_recipientlists')." cr,
																								".DbHelper::addTablePrefix('sproutemail_recipient_lists')." r
																							WHERE 	
																								cr.recipientListId = r.id 
																							AND 
																								cr.campaignId = ".$campaign["id"]
		)->queryAll();

		// Put the results into the campaign_id variable 
		// or the list_email array based on their type 
		$campaign_id = '';
		$list_email  = array();

		foreach ($recipients as $rec)
		{
			if ($rec["type"] == 'campaign')
			{
				$campaign_id = $rec["val"];
			}
			else
			{
				$list_email[] = $rec["val"];
			}
		}

		foreach ($list_email as $send)
		{
			$mgClient = new Mailgun($this->apiKey);

			# Next, instantiate a Message Builder object from the SDK.
			$msgBldr = $mgClient->MessageBuilder();

			$domain = $this->domain;
			# Make the call to the client.
			$result = $mgClient->sendMessage(
				$domain, array(
					'from'       => $campaign["fromName"].' <'.$campaign["fromEmail"].'>',
					'to'         => $send,
					'subject'    => htmlspecialchars($emailSubject),
					'html'       => $htmlContent,
					'text'       => $textContent,
					'o:campaign' => $campaign_id
				)
			);
		}

		// Give back a message
		echo 'Email sent to Mailgun for delivery';

		// All done. Only do it once! 
		$this->export_once++;
	}

	/**
	 * Exports campaign (with send)
	 *
	 * @param array $campaign
	 * @param array $listIds
	 */
	public function sendEntry($campaign = array(), $listIds = array())
	{
	}

	public function saveRecipientList(SproutEmail_CampaignModel &$campaign, SproutEmail_CampaignRecord &$campaignRecord)
	{
		// an email provider is required
		if (!isset($campaign->emailProvider) || !$campaign->emailProvider)
		{
			$campaign->addError('emailProvider', 'Unsupported email provider.');

			return false;
		}

		// Start with some housecleaning
		$recipients = craft()->db->createCommand(
			"DELETE FROM
																								".DbHelper::addTablePrefix('sproutemail_recipientlists')."
																							WHERE 
																								id IN 
																										(
																											SELECT 
																													recipientListId 
																											FROM 
																													".DbHelper::addTablePrefix('sproutemail_campaigns_recipientlists')." 
																											WHERE 	
																													campaignId = ".$campaign["id"]."
																										)"
		)->query();

		foreach ($campaign->emailProviderRecipientListId as $key => $val)
		{
			if (!is_array($val))
			{
				$tmp = $val;
				unset($val);
				$val[0] = $tmp;
			}

			foreach ($val as $value)
			{
				$recipientListRecord                               = new SproutEmail_EntryRecipientListRecord();
				$recipientListRecord->emailProviderRecipientListId = $value;
				$recipientListRecord->type                         = $key;
				$recipientListRecord->emailProvider                = $campaign->emailProvider;

				if ($recipientListRecord->save())
				{
					// associate with campaign, if not already done so
					if (SproutEmail_CampaignRecipientListRecord::model()->count(
							'recipientListId=:recipientListId AND campaignId=:campaignId', array(
								':recipientListId' => $recipientListRecord->id,
								':campaignId'      => $campaignRecord->id
							)
						) == 0
					)
					{
						$campaignRecipientListRecord                  = new SproutEmail_CampaignRecipientListRecord();
						$campaignRecipientListRecord->recipientListId = $recipientListRecord->id;
						$campaignRecipientListRecord->campaignId      = $campaignRecord->id;
						$campaignRecipientListRecord->save(false);
					}
				}
			}
		}

		return true;
	}
}
