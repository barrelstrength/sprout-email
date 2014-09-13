<?php
namespace Craft;

/**
 * SendGrid service
 * Abstracted SendGrid wrapper
 */
class SproutEmail_MailGunService extends SproutEmail_EmailProviderService implements SproutEmail_EmailProviderInterfaceService
{
	protected $apiSettings;
	protected $api_key;
	protected $domain;
	
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
		
		$res = SproutEmail_EmailProviderSettingRecord::model()->find( $criteria );
		$this->apiSettings = json_decode( $res->apiSettings );
		
		$this->api_key = isset( $this->apiSettings->api_key ) ? $this->apiSettings->api_key : '';
		$this->domain = isset( $this->apiSettings->domain ) ? $this->apiSettings->domain : '';
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
	}
	
	/**
	 * Get settings
	 * 
	 * @return obj
	 */
	public function getSettings()
	{
		if ( $this->api_key && $this->domain )
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
		echo 'here';
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
