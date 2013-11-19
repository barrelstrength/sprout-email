<?php
namespace Craft;

/**
 * MasterBlaster service
 */
class MasterBlaster_masterblasterService extends MasterBlaster_EmailProviderService
{
	/**
	 * Returns locally managed recipient lists (used by masterBlaster email provider service)
	 * 
	 * @return multitype:|multitype:NULL
	 */	
	public function getSubscriberList()
	{
		$subscriber_lists = array();
		$localRecipientLists = MasterBlaster_LocalRecipientListRecord::model()->findAll();
		
		if(count($localRecipientLists) == 0)
		{
			return $subscriber_lists;
		}
		 
		foreach($localRecipientLists as $list)
		{
			$subscriber_lists[$list->id] = $list->name;
		}
		 
		return $subscriber_lists;
	}
	
	/**
	 * Exports campaign (no send)
	 *
	 * @param array $campaign
	 * @param array $listIds
	 */
	public function exportCampaign($campaign = array(), $listIds = array())
	{
		die('Not supported for this provider.');
	}
	
	/**
	 * Exports campaign (with send)
	 *
	 * @param array $campaign
	 * @param array $listIds
	 */
	public function sendCampaign($campaign = array(), $listIds = array())
	{
		// get recipients 
		$criteria = new \CDbCriteria();
		$criteria->addInCondition('localRecipientList.id', $listIds);
		$recipients = MasterBlaster_RecipientRecord::model()->with('localRecipientList')->findAll($criteria);
		
		$emailData = array(
				'fromEmail'         => $campaign->fromEmail,
				'fromName'          => $campaign->fromName,
				'subject'           => $campaign->subject,
				'body'              => $campaign->textBody,
				'htmlBody'          => $campaign->textBody
		);
		$recipients = explode("\r\n", $campaign->recipients);
		Craft::dump($recipients);die();
		$emailModel = EmailModel::populateModel($emailData);
		
		foreach($recipients as $recipient)
		{
			$emailModel->toEmail = $recipient->email;
			craft()->email->sendEmail($emailModel);
		}
	}
}
