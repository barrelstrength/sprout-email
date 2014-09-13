<?php
namespace Craft;

use Guzzle\Http\Client;

/**
 * CopyPaste service
 * Abstracted CopyPaste wrapper
 */
class SproutEmail_CopyPasteService extends SproutEmail_EmailProviderService implements SproutEmail_EmailProviderInterfaceService
{
	protected $apiSettings;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->apiSettings = new \stdClass();
	}
	
	/**
	 * Returns subscriber lists
	 *
	 * @return array
	 */
	public function getSubscriberList()
	{
		require_once (dirname( __FILE__ ) . '/../libraries/CopyPaste/CopyPaste/newsletter.php');
		
		$subscriber_lists = array ();
		
		$CopyPaste = new \CopyPasteNewsletter($this->api_user,$this->api_key); 
	
		$result = $CopyPaste->newsletter_lists_get();
		
		if ( ! $result)
		{
			return $subscriber_lists;
		}
	
		foreach ( $result as $v )
		{
			$subscriber_lists [$v['list']] = $v['list'];
		}
		
		return $subscriber_lists;
	}
	
	/**
	 * Exports campaign (no send)
	 *
	 * @param array $campaign            
	 * @param array $listIds            
	 */
	public function exportCampaign($campaign = array(), $listIds = array(), $return = false)
	{	
	    $arr = array();
    	$client = new Client('http://'.$_SERVER["HTTP_HOST"]);

        // Get the HTML version
            $request = $client->get('/'.$campaign['htmlBodyTemplate'].'?entryId='.$campaign['entryId']);
                            
            // send request / get response
                $response = $request->send();
            
            // The body of the email
                $arr["html"] = trim($response->getBody());

        // Get the Text Version
            $request = $client->get('/'.$campaign['textBodyTemplate'].'?entryId='.$campaign['entryId']);
                            
        // send request / get response
            $response = $request->send();
        
        // The body of the email
            $arr["text"] = trim($response->getBody());
        
            echo json_encode($arr);
            unset($client);
		exit;
	}
	
	/**
	 * Get settings
	 * 
	 * @return obj
	 */
	public function getSettings()
	{
		$this->apiSettings->valid = true;
		return $this->apiSettings;
	}
	
	/**
	 * Save settings
	 * 
	 * @param array $settings
	 * @return bool
	 */
	public function saveSettings($settings = array())
	{
		$criteria = new \CDbCriteria();
		$criteria->condition = 'emailProvider=:emailProvider';
		$criteria->params = array (
			':emailProvider' => 'CopyPaste'
		);
		
		$record = SproutEmail_EmailProviderSettingRecord::model()->find( $criteria );
		$record->apiSettings = json_encode( $settings );
		return $record->save();
	}

	public function sendCampaign($campaign = array(), $listIds = array())
	{
		
	}
	
	public function getSenderAddresses()
	{

	}
}
