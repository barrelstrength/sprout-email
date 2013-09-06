<?php
namespace Craft;

/**
 * MailChimp service
 */
class MasterBlaster_mail_chimpService extends MasterBlaster_EmailProviderService implements MasterBlaster_EmailProviderInterfaceService
{	
	public function getSubscriberList()
	{
		require_once(dirname(__FILE__) . '/../libraries/mail_chimp/inc/MCAPI.class.php');
		require_once(dirname(__FILE__) . '/../libraries/mail_chimp/inc/config.inc.php'); //contains apikey
		$subscriber_lists = array();
		
		$api = new \MCAPI($apikey);
		$res = $api->lists();
		
		if ($api->errorCode)
		{
			return $subscriber_lists;
		} 
		
		foreach ($res['data'] as $list)
		{
			$subscriber_lists[$list['id']] = $list['name'];
		}
		 
		return $subscriber_lists;
	}
	
	public function exportCampaign($campaign = array(), $listIds = array())
	{
		require_once(dirname(__FILE__) . '/../libraries/mail_chimp/inc/MCAPI.class.php');
		require_once(dirname(__FILE__) . '/../libraries/mail_chimp/inc/config.inc.php'); //contains apikey

		$api = new \MCAPI($apikey);
		
		$type = 'regular';		
		$opts = array(
				'subject' => $campaign['subject'],
				'title' => $campaign['name'],
				'from_name' => $campaign['fromName'],
				'from_email' => $campaign['fromEmail'],
				
				// need to look into this
				'tracking' => array('opens' => true, 'html_clicks' => true, 'text_clicks' => false),
				
				'authenticate' => true,
				'list_id' => $listIds[0]
		);
		
		$ch = curl_init();		
		curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		
		$html_url = craft()->getBaseUrl(true) . '/'
				. $campaign['htmlTemplate']
				. "?sectionId={$campaign['sectionId']}"
				. "&handle={$campaign['handle']}"
				. "&entryId={$campaign['entryId']}"
				. "&slug={$campaign['slug']}";
		curl_setopt($ch, CURLOPT_URL,$html_url);
		$html = curl_exec($ch);

		$text_url = craft()->getBaseUrl(true) . '/'
				. $campaign['textTemplate']
				. "?sectionId={$campaign['sectionId']}"
				. "&handle={$campaign['handle']}"
				. "&entryId={$campaign['entryId']}"
				. "&slug={$campaign['slug']}";
		curl_setopt($ch, CURLOPT_URL,$text_url);
		$text = curl_exec($ch);
		
		$content = array(
				'html' => $html,
				'text' => $text
		);
		
		$result = $api->campaignCreate($type, $opts, $content);	
		
		if( ! $api->errorCode) 
			echo "Created with ID ".$result;
		else 
			echo 'Error: ' . $api->errorMessage;

		die();
	}
	
	public function sendCampaign($campaign = array(), $listIds = array())
	{
		// TODO
	}
}
