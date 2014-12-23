<?php
namespace Craft;

/**
 * CampaignMonitor service
 * Abstracted CampaignMonitor wrapper
 */
class SproutEmail_CampaignMonitorService extends SproutEmail_EmailProviderService implements SproutEmailProviderInterface
{
	protected $apiSettings;
	protected $clientId;
	protected $apiKey;
	public function __construct()
	{
		$criteria = new \CDbCriteria();
		$criteria->condition = 'emailProvider=:emailProvider';
		$criteria->params = array (
				':emailProvider' => 'CampaignMonitor' 
		);
		
		$res = SproutEmail_EmailProviderSettingsRecord::model()->find( $criteria );
		$this->apiSettings = json_decode( $res->apiSettings );
		
		$customConfigSettings = craft()->config->get('sproutEmail');
		
		$this->clientId = (isset($customConfigSettings['apiSettings']['CampaignMonitor']['clientId'])) ? $customConfigSettings['apiSettings']['CampaignMonitor']['clientId'] : (isset( $this->apiSettings->clientId ) ? $this->apiSettings->clientId : '');
		$this->apiKey = (isset($customConfigSettings['apiSettings']['CampaignMonitor']['apiKey'])) ? $customConfigSettings['apiSettings']['CampaignMonitor']['apiKey'] : (isset( $this->apiSettings->apiKey ) ? $this->apiSettings->apiKey : '');

		$this->apiSettings->clientId = $this->clientId;
		$this->apiSettings->apiKey = $this->apiKey;
	}
	/**
	 * Returns subscriber lists
	 *
	 * @return array
	 */
	public function getSubscriberList()
	{
		// http://www.campaignmonitor.com/api/clients/#subscriber_lists
		require_once(dirname(__FILE__).'/../libraries/CampaignMonitor/csrest_clients.php');
		$subscriberLists = array ();

		$wrap = new \CS_REST_Clients( $this->clientId, $this->apiKey );
		$result = $wrap->get_lists();
		
		if ( ! is_array( $result->response ) || ! isset( $result->response [0]->ListID ) )
		{
			return $subscriberLists;
		}
		
		foreach ( $result->response as $v )
		{
			$subscriberLists [$v->ListID] = $v->Name;
		}
		
		return $subscriberLists;
	}
	
	/**
	 * Exports campaign (no send)
	 *
	 * @param array $campaign            
	 * @param array $listIds            
	 */
	public function exportEntry($campaign = array(), $listIds = array(), $return = false)
	{
		// http://www.campaignmonitor.com/api/clients/#subscriber_lists
		require_once(dirname(__FILE__).'/../libraries/CampaignMonitor/csrest_campaigns.php');
		$wrap = new \CS_REST_Campaigns( NULL, $this->apiKey );
		
		$result = $wrap->create( $this->clientId, array(
			'Subject' => $campaign ['title'],
			'Name' => $campaign ['name'],
			'FromName' => $campaign ['fromName'],
			'FromEmail' => $campaign ['fromEmail'],
			'ReplyTo' => $campaign ['replyToEmail'],
			'HtmlUrl' => craft()->getBaseUrl( true ) . '/' . $campaign ['htmlTemplate'] . "?sectionId={$campaign['sectionId']}" . "&handle={$campaign['handle']}" . "&entryId={$campaign['entryId']}" . "&slug={$campaign['slug']}",
			'TextUrl' => craft()->getBaseUrl( true ) . '/' . $campaign ['textTemplate'] . "?sectionId={$campaign['sectionId']}" . "&handle={$campaign['handle']}" . "&entryId={$campaign['entryId']}" . "&slug={$campaign['slug']}",
			'ListIDs' => $listIds 
		));
		
		if ( $result->was_successful() )
			echo "Created with ID " . $result->response;
		else
			echo 'Error: ' . $result->response->Message;
		
		die();
	}

	public function getSettings()
	{
		if ( $this->apiKey && $this->clientId )
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
		$criteria->params = array (
			':emailProvider' => 'CampaignMonitor'
		);
		
		$record = SproutEmail_EmailProviderSettingsRecord::model()->find( $criteria );
		$record->apiSettings = json_encode( $settings );
		return $record->save();
	}
	
	/**
	 * Exports campaign (with send)
	 *
	 * @param array $campaign            
	 * @param array $listIds            
	 */
	public function sendEntry($campaign = array(), $listIds = array())
	{
		// Future feature
	}
}
