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
	protected $apiUser;
	protected $apiKey;
	
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
		
		$res = SproutEmail_EmailProviderSettingsRecord::model()->find( $criteria );
		$this->apiSettings = json_decode( $res->apiSettings );
		
		$customConfigSettings = craft()->config->get('sproutEmail');
		
		$this->apiUser = (isset($customConfigSettings['apiSettings']['SendGrid']['apiUser'])) ? $customConfigSettings['apiSettings']['SendGrid']['apiUser'] : (isset( $this->apiSettings->apiUser ) ? $this->apiSettings->apiUser : '');
		$this->apiKey = (isset($customConfigSettings['apiSettings']['SendGrid']['apiKey'])) ? $customConfigSettings['apiSettings']['SendGrid']['apiKey'] : (isset( $this->apiSettings->apiKey ) ? $this->apiSettings->apiKey : '');

		$this->apiSettings->apiUser = $this->apiUser;
		$this->apiSettings->apiKey = $this->apiKey;
	}
	
	/**
	 * Returns subscriber lists
	 *
	 * @return array
	 */
	public function getSubscriberList()
	{
		require_once (dirname( __FILE__ ) . '/../libraries/SendGrid/sendgrid/newsletter.php');
		
		$subscriberLists = array ();
		
		$sendgrid = new \sendgridNewsletter($this->apiUser,$this->apiKey); 
	
		$result = $sendgrid->newsletter_lists_get();
		
		if ( ! $result)
		{
			return $subscriberLists;
		}
		
		foreach ( $result as $v )
		{
			// Array
			// (
			//     [Test 2] => Test 2
			//     [Test List] => Test List
			// )

			$subscriberLists [$v['list']] = $v['list'];
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
		// Turn off devMode output (@TODO - this doesn't work)
		craft()->log->removeRoute('WebLogRoute');
		craft()->log->removeRoute('ProfileLogRoute');

		require_once (dirname( __FILE__ ) . '/../libraries/SendGrid/sendgrid/newsletter.php');
		$sendgrid = new \sendgridNewsletter($this->apiUser,$this->apiKey); 
		
		// Create an instance of the guzzle client
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
		$endpoint = ($protocol.$_SERVER['HTTP_HOST']);
		$client = new Client($endpoint);

		// Get the HTML
		$request = $client->get('/'.$emailBlastType["htmlTemplate"].'?entryId='.$emailBlastType["entryId"]);
		$response = $request->send();
		$html = trim($response->getBody());

		// Get the text
		$request = $client->get("/".$emailBlastType ['textTemplate'] . "?entryId={$emailBlastType['entryId']}");
		$response = $request->send();
		$text = trim($response->getBody());

		// Get the Entry
		$entry = craft()->entries->getEntryById($emailBlastType["entryId"]);


		// check if newsletter exists
		$res = $sendgrid->newsletter_get($entry->title);
		
		// Get the subject
		// @TODO - update this to be the Email Blast Element 
		$subject = $entry->title;
						
		// need to create the template (newsletter)
		if( ! $res)
		{
			$res = $sendgrid->newsletter_add($emailBlastType ['fromName'], $entry->title , $subject , $text , $html);
		}
		
		if( $error = $sendgrid->getLastResponseError())
		{
			die($error);
		}
		
		// if sender address has changed, update the newsletter
		if(isset($res['identity']) && $res['identity'] != $emailBlastType['fromName'])
		{
			$res = $sendgrid->newsletter_edit($emailBlastType ['fromName'], $res['name'] , $entry->title, $entry->title , $text , $html);
						
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
		// $res = $sendgrid->newsletter_schedule_add($emailBlastType ['name']);

		// if( $error = $sendgrid->getLastResponseError())
		// {
		// 	die($error);
		// }
		
		// customize messages
		if ($res['message'])
		{
			switch($res['message'])
			{
				case 'success':
					$msg["response"] = 'Your emailBlastType has been successfully exported. Please login to SendGrid to complete your email blast.';
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
		if ( $this->apiKey && $this->apiUser )
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
		
	}
	
	public function getSenderAddresses()
	{
		require_once (dirname( __FILE__ ) . '/../libraries/SendGrid/sendgrid/newsletter.php');
		$sendgrid = new \sendgridNewsletter($this->apiUser,$this->apiKey);
		
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
