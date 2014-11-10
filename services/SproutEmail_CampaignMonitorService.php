<?php
namespace Craft;

/**
 * CampaignMonitor service
 * Abstracted CampaignMonitor wrapper
 */
class SproutEmail_CampaignMonitorService extends SproutEmail_EmailProviderService implements SproutEmail_EmailProviderInterfaceService
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
		require_once (dirname( __FILE__ ) . '/../libraries/CampaignMonitor/csrest_clients.php');
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
	 * Exports emailBlastType (no send)
	 *
	 * @param array $emailBlastType            
	 * @param array $listIds            
	 */
	public function exportEmailBlast($emailBlastType = array(), $listIds = array(), $return = false)
	{
		// http://www.campaignmonitor.com/api/clients/#subscriber_lists
		require_once (dirname( __FILE__ ) . '/../libraries/CampaignMonitor/csrest_campaigns.php');
		$wrap = new \CS_REST_Campaigns( NULL, $this->apiKey );
		
		$result = $wrap->create( $this->clientId, array(
			'Subject' => $emailBlastType ['title'],
			'Name' => $emailBlastType ['name'],
			'FromName' => $emailBlastType ['fromName'],
			'FromEmail' => $emailBlastType ['fromEmail'],
			'ReplyTo' => $emailBlastType ['replyToEmail'],
			'HtmlUrl' => craft()->getBaseUrl( true ) . '/' . $emailBlastType ['htmlTemplate'] . "?sectionId={$emailBlastType['sectionId']}" . "&handle={$emailBlastType['handle']}" . "&entryId={$emailBlastType['entryId']}" . "&slug={$emailBlastType['slug']}",
			'TextUrl' => craft()->getBaseUrl( true ) . '/' . $emailBlastType ['textTemplate'] . "?sectionId={$emailBlastType['sectionId']}" . "&handle={$emailBlastType['handle']}" . "&entryId={$emailBlastType['entryId']}" . "&slug={$emailBlastType['slug']}",
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
	 * Exports emailBlastType (with send)
	 *
	 * @param array $emailBlastType            
	 * @param array $listIds            
	 */
	public function sendEmailBlast($emailBlastType = array(), $listIds = array())
	{
		// Future feature
	}
}
