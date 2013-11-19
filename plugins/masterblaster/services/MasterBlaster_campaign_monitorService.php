<?php
namespace Craft;

/**
 * CampaignMonitor service
 * Abstracted CampaignMonitor wrapper
 */
class MasterBlaster_campaign_monitorService extends MasterBlaster_EmailProviderService
{
	/**
	 * Returns subscriber lists
	 * 
	 * @return array
	 */
	public function getSubscriberList()
	{
		// http://www.campaignmonitor.com/api/clients/#subscriber_lists
		require_once(dirname(__FILE__) . '/../libraries/campaign_monitor/csrest_clients.php');
		$subscriber_lists = array();
	
		$wrap = new \CS_REST_Clients('e2da5a2cc2c2040857eb2f8add0726c1', '718251f99548f57c6a278d0439da2c51cc5fd5696194282f');
		$result = $wrap->get_lists();
		if( ! is_array($result->response) || ! isset($result->response[0]->ListID))
		{
			return $subscriber_lists;
		}
		 
		foreach($result->response as $v)
		{
			$subscriber_lists[$v->ListID] = $v->Name;
		}
		 
		return $subscriber_lists;
	}
	
	/**
	 * Exports campaign (no send)
	 * 
	 * @param array $campaign
	 * @param array $listIds
	 */
	public function exportCampaign($campaign = array(), $listIds = array())
	{
		// http://www.campaignmonitor.com/api/clients/#subscriber_lists
		require_once(dirname(__FILE__) . '/../libraries/campaign_monitor/csrest_campaigns.php');
		$wrap = new \CS_REST_Campaigns(NULL, '718251f99548f57c6a278d0439da2c51cc5fd5696194282f');

		$result = $wrap->create('e2da5a2cc2c2040857eb2f8add0726c1', array(
				'Subject' => $campaign['subject'],
				'Name' => $campaign['name'],
				'FromName' => $campaign['fromName'],
				'FromEmail' => $campaign['fromEmail'],
				'ReplyTo' => $campaign['replyToEmail'],
				'HtmlUrl' => craft()->getBaseUrl(true) . '/'
				. $campaign['htmlTemplate']
				. "?sectionId={$campaign['sectionId']}"
				. "&handle={$campaign['handle']}"
				. "&entryId={$campaign['entryId']}"
				. "&slug={$campaign['slug']}",
				'TextUrl' => craft()->getBaseUrl(true) . '/'
				. $campaign['textTemplate']
				. "?sectionId={$campaign['sectionId']}"
				. "&handle={$campaign['handle']}"
				. "&entryId={$campaign['entryId']}"
				. "&slug={$campaign['slug']}",
				'ListIDs' => $listIds
		));
		
		
		if($result->was_successful()) 
			echo "Created with ID ".$result->response;
		else
			echo 'Error: ' . $result->response->Message;

		die();
	}
	
	/**
	 * Exports campaign (with send)
	 * 
	 * @param array $campaign
	 * @param array $listIds
	 */
	public function sendCampaign($campaign = array(), $listIds = array())
	{
		// Future feature
	}
}
