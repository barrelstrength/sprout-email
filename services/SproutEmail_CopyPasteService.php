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
	 * Exports emailBlastType (no send)
	 *
	 * @param array $emailBlastType            
	 * @param array $listIds            
	 */
	public function exportEmailBlast($emailBlastType = array(), $listIds = array(), $return = false)
	{	
		$arr = array();
		$client = new Client('http://'.$_SERVER["HTTP_HOST"]);

		// Get the HTML version
		$request = $client->get('/'.$emailBlastType['htmlBodyTemplate'].'?entryId='.$emailBlastType['entryId']);
													
		// send request / get response
		$response = $request->send();
					
		// The body of the email
		$arr["html"] = trim($response->getBody());

		// Get the Text Version
		$request = $client->get('/'.$emailBlastType['textBodyTemplate'].'?entryId='.$emailBlastType['entryId']);
													
		// send request / get response
		$response = $request->send();
			
		// The body of the email
		$arr["text"] = trim($response->getBody());
					
		// Show name of email, name of selected list and name of sender?     
		$arr["title"]       = $emailBlastType["title"];
		$arr["fromEmail"]  = $emailBlastType["fromEmail"];
		$arr["fromName"]   = $emailBlastType["fromName"];
							
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

	public function sendEmailBlast($emailBlastType = array(), $listIds = array())
	{
		
	}
	
	public function getSenderAddresses()
	{

	}
}
