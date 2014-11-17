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
	protected $enabled;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$criteria = new \CDbCriteria();
		$criteria->condition = 'emailProvider=:emailProvider';
		$criteria->params = array (
				':emailProvider' => 'CopyPaste' 
		);

		$res = SproutEmail_EmailProviderSettingsRecord::model()->find( $criteria );
		$this->apiSettings = json_decode( $res->apiSettings );

		$customConfigSettings = craft()->config->get('sproutEmail');
		
		$this->enabled = (isset($customConfigSettings['apiSettings']['CopyPaste']['enabled'])) ? $customConfigSettings['apiSettings']['CopyPaste']['enabled'] : (isset( $this->apiSettings->enabled ) ? $this->apiSettings->enabled : 0);

		$this->apiSettings->enabled = $this->enabled;
	}
	
	/**
	 * Returns subscriber lists
	 *
	 * @return array
	 */
	public function getSubscriberList()
	{
		require_once (dirname( __FILE__ ) . '/../libraries/CopyPaste/CopyPaste/newsletter.php');
		
		$subscriberLists = array ();
		
		$CopyPaste = new \CopyPasteNewsletter($this->apiUser,$this->apiKey); 
	
		$result = $CopyPaste->newsletter_lists_get();
		
		if ( ! $result)
		{
			return $subscriberLists;
		}
	
		foreach ( $result as $v )
		{
			$subscriberLists [$v['list']] = $v['list'];
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
		$arr = array();
		$client = new Client('http://'.$_SERVER["HTTP_HOST"]);

		// Get the HTML version
		$request = $client->get('/'.$campaign['templateCopyPaste'].'?entryId='.$campaign['entryId']);
													
		// send request / get response
		$response = $request->send();
					
		// The body of the email
		$arr["html"] = trim($response->getBody());

		// Get the Text Version
		$request = $client->get('/'.$campaign['templateCopyPaste'].'.txt?entryId='.$campaign['entryId']);
													
		// send request / get response
		$response = $request->send();
			
		// The body of the email
		$arr["text"] = trim($response->getBody());
					
		// Show name of email, name of selected list and name of sender?     
		$arr["title"]      = $campaign["title"];
		$arr["fromEmail"]  = $campaign["fromEmail"];
		$arr["fromName"]   = $campaign["fromName"];
							
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
		
		$record = SproutEmail_EmailProviderSettingsRecord::model()->find( $criteria );
		$record->apiSettings = json_encode( $settings );
		return $record->save();
	}

	public function sendEntry($campaign = array(), $listIds = array())
	{
		
	}
	
	public function getSenderAddresses()
	{

	}
}
