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
		$criteria->params = array (
				':emailProvider' => 'CampaignMonitor' 
		);
		
		$res = SproutEmail_EmailProviderSettingRecord::model()->find( $criteria );
		$this->apiSettings = json_decode( $res->apiSettings );
		
		$this->client_id = isset( $this->apiSettings->client_id ) ? $this->apiSettings->client_id : '';
		$this->api_key = isset( $this->apiSettings->api_key ) ? $this->apiSettings->api_key : '';
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
		$subscriber_lists = array ();
		
		$wrap = new \CS_REST_Clients( $this->client_id, $this->api_key );
		$result = $wrap->get_lists();
		if ( ! is_array( $result->response ) || ! isset( $result->response [0]->ListID ) )
		{
			return $subscriber_lists;
		}
		
		foreach ( $result->response as $v )
		{
			$subscriber_lists [$v->ListID] = $v->Name;
		}
		
		return $subscriber_lists;
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
		$wrap = new \CS_REST_Campaigns( NULL, $this->api_key );
		
		$result = $wrap->create( $this->client_id, array(
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
		if ( $this->api_key && $this->client_id )
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
		
		$record = SproutEmail_EmailProviderSettingRecord::model()->find( $criteria );
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
