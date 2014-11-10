<?php
namespace Craft;

require_once (dirname( __FILE__ ) . '/../vendor/autoload.php');

use Mailgun\Mailgun;
use Mailgun\Connection;
use Guzzle\Http\Client;

/**
 * SendGrid service
 * Abstracted SendGrid wrapper
 */
class SproutEmail_MailGunService extends SproutEmail_EmailProviderService implements SproutEmail_EmailProviderInterfaceService
{
	protected $apiSettings;
	protected $apiKey;
	protected $domain;
	
	private $export_once;     # Track that we only run the export once. 
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$criteria = new \CDbCriteria();
		$criteria->condition = 'emailProvider=:emailProvider';
		$criteria->params = array (
			':emailProvider' => 'MailGun' 
		);
		
		$res = SproutEmail_EmailProviderSettingsRecord::model()->find( $criteria );
		$this->apiSettings = json_decode( $res->apiSettings );
		
		$customConfigSettings = craft()->config->get('sproutEmail');
		
		$this->apiKey = (isset($customConfigSettings['apiSettings']['MailGun']['apiKey'])) ? $customConfigSettings['apiSettings']['MailGun']['apiKey'] : (isset( $this->apiSettings->apiKey ) ? $this->apiSettings->apiKey : '');
		$this->domain = (isset($customConfigSettings['apiSettings']['MailGun']['domain'])) ? $customConfigSettings['apiSettings']['MailGun']['domain'] : (isset( $this->apiSettings->domain ) ? $this->apiSettings->domain : '');

		$this->apiSettings->apiKey = $this->apiKey;
		$this->apiSettings->domain = $this->domain;
	}
	
	/**
	 * Returns mailing lists
	 *
	 * @return array
	 */
	public function getSubscriberList()
	{
		// Container
		$subscriber_lists = array ();
	
		// List request from Mailgun
		$mgClient = new Mailgun($this->apiKey);
		$result = $mgClient->get("lists");
		$response = $result->http_response_body;

		foreach($response->items as $list)
		{
			$subscriber_lists[$list->address] = '<b>'.$list->name.'</b> ('.$list->members_count.') <span class="info">'.$list->description.'</span>';
		}

		return $subscriber_lists;
	}
	
	public function getEmailBlastTypeList()
	{
		$emailblasts = array();
			
		// List the emailBlastType options
		$mgClient = new Mailgun($this->apiKey);
		$result = $mgClient->get($this->domain."/emailblasts");
		$response = $result->http_response_body;        

		$emailblasts[] = array(
			'label' => 'No EmailBlastType',
			'value' => ''
		);
		
		foreach($response->items as $list)
		{
			$emailblasts[] = array(
				'label' => $list->name,
				'value'=>$list->id
		 	);
		}

		return $emailblasts;
	}
	
	/**
	 * Exports emailBlastType (no send)
	 *
	 * @param array $emailBlastType            
	 * @param array $listIds            
	 */
	public function exportEmailBlast($emailBlastType = array(), $listIds = array(), $return = false)
	{
		// We only want to run once! 
		if($this->export_once == 1)
		{
			return;
		}

		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
		$endpoint = ($protocol.$_SERVER['HTTP_HOST']);
		$client = new Client($endpoint);
		
		// send request / get response / grab body of email
		$request = $client->get('/'.$emailBlastType["htmlTemplate"].'?entryId='.$emailBlastType["entryId"]);
		$response = $request->send();
		$htmlContent = trim($response->getBody());

		// send request / get response / grab body of text email
		$textRequest = $client->get('/'.$emailBlastType["textTemplate"].'?entryId='.$emailBlastType["entryId"]);
		$textResponse = $textRequest->send();
		$textContent = trim($textResponse->getBody());

		// Get the subject of the email from the entry
		// @TODO update this to use the Email Blast Element
		$entry = craft()->entries->getEntryById($emailBlastType["entryId"]);
		$emailSubject = $entry->title;

		// Convert encoded quotes
		// http://stackoverflow.com/questions/1262038/how-to-replace-microsoft-encoded-quotes-in-php
		$emailSubject = iconv('UTF-8', 'ASCII//TRANSLIT', $emailSubject);

		// Send to mailgun				
		$recipients = craft()->db->createCommand("SELECT 
																								r.type, 
																								r.emailProviderRecipientListId as val 
																							FROM 
																								".DbHelper::addTablePrefix('sproutemail_emailblasttypes_recipientlists')." cr,
																								".DbHelper::addTablePrefix('sproutemail_recipient_lists')." r
																							WHERE 	
																								cr.recipientListId = r.id 
																							AND 
																								cr.emailBlastTypeId = ".$emailBlastType["id"])->queryAll();

		// Put the results into the emailBlastType_id variable 
		// or the list_email array based on their type 
		$emailBlastType_id = '';
		$list_email  = array();

		foreach($recipients as $rec)
		{
			if ($rec["type"] == 'emailBlastType')
			{
				$emailBlastType_id = $rec["val"];
			}
			else
			{
				$list_email[]= $rec["val"];
			}
		}

		foreach($list_email as $send)
		{
			$mgClient = new Mailgun($this->apiKey);

			# Next, instantiate a Message Builder object from the SDK.
			$msgBldr = $mgClient->MessageBuilder();

			$domain = $this->domain;
			# Make the call to the client.
			$result = $mgClient->sendMessage($domain, array(
					'from'       => $emailBlastType["fromName"].' <'.$emailBlastType["fromEmail"].'>',
					'to'         => $send,
					'subject'    => htmlspecialchars($emailSubject),
					'html'       => $htmlContent,
					'text'       => $textContent,
					'o:emailBlastType' => $emailBlastType_id
			));                   
		}

		// Give back a message
		echo 'Email sent to Mailgun for delivery';

		// All done. Only do it once! 
		$this->export_once++;
	}
	
	/**
	 * Get settings
	 * 
	 * @return obj
	 */
	public function getSettings()
	{
		if ( $this->apiKey && $this->domain )
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
			':emailProvider' => 'MailGun'
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
	
	public function saveRecipientList(SproutEmail_EmailBlastTypeModel &$emailBlastType, SproutEmail_EmailBlastTypeRecord &$emailBlastTypeRecord)
	{
		// an email provider is required
		if ( ! isset( $emailBlastType->emailProvider ) || ! $emailBlastType->emailProvider )
		{
			$emailBlastType->addError( 'emailProvider', 'Unsupported email provider.' );
			return false;
		}

		// Start with some housecleaning
		$recipients = craft()->db->createCommand("DELETE FROM 
																								".DbHelper::addTablePrefix('sproutemail_recipientlists')."
																							WHERE 
																								id IN 
																										(
																											SELECT 
																													recipientListId 
																											FROM 
																													".DbHelper::addTablePrefix('sproutemail_emailblasttypes_recipientlists')." 
																											WHERE 	
																													emailBlastTypeId = ".$emailBlastType["id"]."
																										)")->query();
		
		foreach($emailBlastType->emailProviderRecipientListId as $key => $val)
		{
			if (!is_array($val))
			{
				$tmp = $val;
				unset($val);
				$val[0] = $tmp;
			}

			foreach($val as $value)
			{
				$recipientListRecord = new SproutEmail_RecipientListRecord();
				$recipientListRecord->emailProviderRecipientListId = $value;
				$recipientListRecord->type = $key;
				$recipientListRecord->emailProvider = $emailBlastType->emailProvider;

				if ( $recipientListRecord->save() ) 
				{
					// associate with emailBlastType, if not already done so
					if ( SproutEmail_EmailBlastTypeRecipientListRecord::model()->count( 'recipientListId=:recipientListId AND emailBlastTypeId=:emailBlastTypeId', array (
						':recipientListId'  => $recipientListRecord->id,
						':emailBlastTypeId' => $emailBlastTypeRecord->id 
					) ) == 0 )
					{
						$emailBlastTypeRecipientListRecord = new SproutEmail_EmailBlastTypeRecipientListRecord();
						$emailBlastTypeRecipientListRecord->recipientListId = $recipientListRecord->id;
						$emailBlastTypeRecipientListRecord->emailBlastTypeId = $emailBlastTypeRecord->id;
						$emailBlastTypeRecipientListRecord->save( false );
					}
				}
			}
		}

		return true;
	}
}
