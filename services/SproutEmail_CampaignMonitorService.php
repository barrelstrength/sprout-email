<?php
namespace Craft;

/**
 * CampaignMonitor service
 * Abstracted CampaignMonitor wrapper
 */
class SproutEmail_CampaignMonitorService extends SproutEmail_EmailProviderService implements SproutEmail_EmailProviderInterfaceService
{
    protected $apiSettings;
    
    protected $client_id;
    
    protected $api_key;
    
    public function __construct()
    {        
        $criteria = new \CDbCriteria();
        $criteria->condition = 'emailProvider=:emailProvider';
        $criteria->params = array(':emailProvider' => 'CampaignMonitor');
        
        $res = SproutEmail_EmailProviderSettingRecord::model()->find($criteria);        
        $this->apiSettings = json_decode($res->apiSettings);
        
        $this->client_id = isset($this->apiSettings->client_id) ? $this->apiSettings->client_id : '';
        $this->api_key = isset($this->apiSettings->api_key) ? $this->apiSettings->api_key : '';
        
    }
	/**
	 * Returns subscriber lists
	 * 
	 * @return array
	 */
	public function getSubscriberList()
	{
		// http://www.campaignmonitor.com/api/clients/#subscriber_lists
		require_once(dirname(__FILE__) . '/../libraries/CampaignMonitor/csrest_clients.php');
		$subscriber_lists = array();

		$wrap = new \CS_REST_Clients($this->client_id, $this->api_key);
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
		require_once(dirname(__FILE__) . '/../libraries/CampaignMonitor/csrest_campaigns.php');
		$wrap = new \CS_REST_Campaigns(NULL, $this->api_key);

		$result = $wrap->create($this->client_id, array(
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
	
	public function getSettings()
	{
	    if($this->api_key && $this->client_id)
	    {
	        $this->apiSettings->valid = true;
	    }
	    else
	    {
	        $this->apiSettings->valid = false;
	    }
	    return $this->apiSettings;
	}

	
	public function saveSettings($settings = array())
	{
	    $criteria = new \CDbCriteria();
	    $criteria->condition = 'emailProvider=:emailProvider';
	    $criteria->params = array(':emailProvider' => 'CampaignMonitor');
	    
	    $record = SproutEmail_EmailProviderSettingRecord::model()->find($criteria);
	    $record->apiSettings = json_encode($settings);
	    return $record->save();
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
