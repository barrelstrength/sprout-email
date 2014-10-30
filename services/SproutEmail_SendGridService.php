<?php
namespace Craft;

require_once (dirname( __FILE__ ) . '/../vendor/autoload.php');

use Guzzle\Http\Client;

/**
 * SendGrid service
 * Abstracted SendGrid wrapper
 */
class SproutEmail_SendGridService extends SproutEmail_EmailProviderService implements SproutEmail_EmailProviderInterfaceService
{
	protected $apiSettings;
	protected $api_user;
	protected $api_key;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$criteria = new \CDbCriteria();
		$criteria->condition = 'emailProvider=:emailProvider';
		$criteria->params = array (
				':emailProvider' => 'SendGrid' 
		);
		
		$res = SproutEmail_EmailProviderSettingRecord::model()->find( $criteria );
		$this->apiSettings = json_decode( $res->apiSettings );
		
		$this->api_user = isset( $this->apiSettings->api_user ) ? $this->apiSettings->api_user : '';
		$this->api_key = isset( $this->apiSettings->api_key ) ? $this->apiSettings->api_key : '';
	}
	
	/**
	 * Returns subscriber lists
	 *
	 * @return array
	 */
	public function getSubscriberList()
	{
		require_once (dirname( __FILE__ ) . '/../libraries/SendGrid/sendgrid/newsletter.php');
		
		$subscriber_lists = array ();
		
		$sendgrid = new \sendgridNewsletter($this->api_user,$this->api_key); 
	
		$result = $sendgrid->newsletter_lists_get();
		
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
		// Turn off devMode output (@TODO - this doesn't work)
		craft()->log->removeRoute('WebLogRoute');
		craft()->log->removeRoute('ProfileLogRoute');

		require_once (dirname( __FILE__ ) . '/../libraries/SendGrid/sendgrid/newsletter.php');
		$sendgrid = new \sendgridNewsletter($this->api_user,$this->api_key); 
		
        // Create an instance of the guzzle client
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $endpoint = ($protocol.$_SERVER['HTTP_HOST']);
            $client = new Client($endpoint);
        
        // Get the HTML
            $request = $client->get('/'.$campaign["htmlTemplate"].'?entryId='.$campaign["entryId"]);
            $response = $request->send();
            $html = trim($response->getBody());

        // Get the text
            $request = $client->get("/".$campaign ['textTemplate'] . "?entryId={$campaign['entryId']}");
            $response = $request->send();
            $text = trim($response->getBody());
            
        // Get the Entry
            $entry = craft()->entries->getEntryById($campaign["entryId"]);


		// check if newsletter exists
    		$res = $sendgrid->newsletter_get($entry->title);
		
        // Get the subject
            $subject = (isset($entry->$campaign["subjectHandle"]) && $entry->$campaign["subjectHandle"] != '') ? $entry->$campaign["subjectHandle"] : $entry->title;
            
		// need to create the template (newsletter)
		if( ! $res)
		{
			$res = $sendgrid->newsletter_add($campaign ['fromName'], $entry->title , $subject , $text , $html);
		}
		
		if( $error = $sendgrid->getLastResponseError())
		{
			die($error);
		}
		
		// if sender address has changed, update the newsletter
		if(isset($res['identity']) && $res['identity'] != $campaign['fromName'])
		{
			$res = $sendgrid->newsletter_edit($campaign ['fromName'], $res['name'] , $entry->title, $entry->title , $text , $html);
						
			if( $error = $sendgrid->getLastResponseError())
			{
				die($error);
			}
		}

		// now we need to assign the recipient list to it
		$res = $sendgrid->newsletter_recipients_add($entry->title , $listIds[0]);
		
		if( $error = $sendgrid->getLastResponseError())
		{
			die($error);
		}
		
		// schedule
// 		$res = $sendgrid->newsletter_schedule_add($campaign ['name']);
		
// 		if( $error = $sendgrid->getLastResponseError())
// 		{
// 			die($error);
// 		}
		
		// customize messages
		if($res['message'])
		{
			switch($res['message'])
			{
				case 'success':
					$msg["response"] = 'Your campaign has been successfully exported. Please login to SendGrid to complete your email blast.';
					break;
				default:
					$msg["response"] = 'Unknown error.';
					break;
			}
		}
		echo json_encode($msg);
		exit;
	}
	
	/**
	 * Get settings
	 * 
	 * @return obj
	 */
	public function getSettings()
	{
		if ( $this->api_key && $this->api_user )
		{
			$this->apiSettings->valid = true;
		}
		else
		{
			$this->apiSettings->valid = false;
		}
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
			':emailProvider' => 'SendGrid'
		);
		
		$record = SproutEmail_EmailProviderSettingRecord::model()->find( $criteria );
		$record->apiSettings = json_encode( $settings );
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
		
	}
	
	public function getSenderAddresses()
	{
		require_once (dirname( __FILE__ ) . '/../libraries/SendGrid/sendgrid/newsletter.php');
		$sendgrid = new \sendgridNewsletter($this->api_user,$this->api_key);
		
		$senderAddresses = array();
		
		if ( $identities = $sendgrid->newsletter_identity_list())
		{
			foreach ($identities as $key => $identity)
			{
				$details = $sendgrid->newsletter_identity_get($identity['identity']);

				$senderAddresses[$identity['identity']] = $identity['identity'] . ' - ' . $details['email'] . ' - ' . $details['name'];
			}
		}

		return $senderAddresses;
	}
}
