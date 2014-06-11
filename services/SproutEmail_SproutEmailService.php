<?php
namespace Craft;

/**
 * SproutEmail service
 */
class SproutEmail_SproutEmailService extends SproutEmail_EmailProviderService implements SproutEmail_EmailProviderInterfaceService
{
	/**
	 * N/A
	 * 
	 * @return multitype:|multitype:NULL
	 */	
	public function getSubscriberList()
	{
        if( ! $reports = craft()->plugins->getPlugin('sproutreports')) {
            return false;
        }
        
        $options = array();
        if( $list = craft()->sproutReports_reports->getAllReportsByAttributes(array('isEmailList' => 1))) {
            foreach($list as $report) {
                $options[$report->id] = $report->name;
            }
        }
        return $options;
	}
	
	/**
	 * Exports campaign (no send)
	 *
	 * @param array $campaign
	 * @param array $listIds
	 */
	public function exportCampaign($campaign = array(), $listIds = array())
	{
	    $campaignModel = craft()->sproutEmail->getCampaign(array('id' => $campaign['id']));
	    if($this->sendCampaign($campaignModel, $listIds) === false) {
	        die('Your campaign can not be sent at this time.');
	    }
		die('Campaign successfully sent.');
	}
	
	/**
	 * Exports campaign (with send)
	 *
	 * @param array $campaign
	 * @param array $listIds
	 */
	public function sendCampaign($campaign = array(), $listIds = array())
	{		
		$emailData = array(
				'fromEmail'         => $campaign->fromEmail,
				'fromName'          => $campaign->fromName,
				'subject'           => $campaign->subject,
				'body'              => $campaign->textBody,
		);
		
		if($campaign->htmlBody)
		{
		    $emailData['htmlBody'] = $campaign->htmlBody;
		}
		
		// since we're allowing unchecked variables as replyTo, let's make sure it's a valid email before adding
		if($campaign->replyToEmail && preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $campaign->replyToEmail))
		{
		    $emailData['replyTo'] = $campaign->replyToEmail;
		}
	
		$recipients = explode(",", $campaign->recipients);
		
		if($recipientLists = craft()->sproutEmail->getCampaignRecipientLists($campaign['id']))
		{
		    // make sure SproutReports is installed
		    if($reports = craft()->plugins->getPlugin('sproutreports')) 
		    {
		        foreach($recipientLists as $recipientList)
		        {
                    if( $report = craft()->sproutReports_reports->getReportById($recipientList->emailProviderRecipientListId)) 
                    {
                         $results = craft()->sproutReports_reports->runReport($report['customQuery']);
                         foreach($results as $row)
                         {
                             if(isset($row['email']))
                             {
                                 $recipients[] = $row['email'];
                             }
                         }
                    }
		        }
		    }
		}
		
		// remove duplicates & blanks
		$recipients = array_unique(array_filter($recipients));
		
		//Craft::dump($emailData);Craft::dump($recipients);die('<br/>To disable test mode and send emails, remove line 57 in ' . __FILE__);
		$emailModel = EmailModel::populateModel($emailData);
		
		$post = craft()->request->getPost();
		foreach($recipients as $recipient)
		{
		    try {
    			$emailModel->toEmail = craft()->templates->renderString($recipient, array('entry' => $post));
    			craft()->email->sendEmail($emailModel);
		    } catch (\Exception $e) {
		        // do nothing
		        return false;
		    }
		}
	}

	/**
	 * Save local recipient list
	 * @param object $campaign
	 * @param object $campaignRecord
	 * @return boolean
	 */
	public function saveRecipientList(SproutEmail_CampaignModel &$campaign, SproutEmail_CampaignRecord &$campaignRecord)
	{
		// an email provider is required
		if( ! isset($campaign->emailProvider) || ! $campaign->emailProvider)
		{
			$campaign->addError('emailProvider', 'Unsupported email provider.');
			return false;
		}
            
            // if we have what we need up to this point,
            // get the recipient list(s) by emailProvider and emailProviderRecipientListId
        $criteria = new \CDbCriteria ();
        $criteria->condition = 'emailProvider=:emailProvider';
        $criteria->params = array (
                ':emailProvider' => $campaign->emailProvider 
        );
        
        if ($recipientListIds = array_filter ( ( array ) $campaign->emailProviderRecipientListId )) 
        {            
            // process each recipient listCampaigns
            foreach ( $recipientListIds as $list_id ) 
            {
                $criteria->condition = 'emailProviderRecipientListId=:emailProviderRecipientListId';
                $criteria->params = array (
                        ':emailProviderRecipientListId' => $list_id 
                );
                $recipientListRecord = SproutEmail_RecipientListRecord::model ()->find ( $criteria );
                
                if (! $recipientListRecord)                 // doesn't exist yet, so we need to create
                {
                    // save record
                    $recipientListRecord = new SproutEmail_RecipientListRecord ();
                    $recipientListRecord->emailProviderRecipientListId = $list_id;
                    $recipientListRecord->emailProvider = $campaign->emailProvider;
                }
                
                // we already did our validation, so just save
                if ($recipientListRecord->save ()) 
                {
                    // associate with campaign, if not already done so
                    if (SproutEmail_CampaignRecipientListRecord::model ()->count ( 'recipientListId=:recipientListId AND campaignId=:campaignId', array (
                            ':recipientListId' => $recipientListRecord->id,
                            ':campaignId' => $campaignRecord->id 
                    ) ) == 0) 
                    {
                        $campaignRecipientListRecord = new SproutEmail_CampaignRecipientListRecord ();
                        $campaignRecipientListRecord->recipientListId = $recipientListRecord->id;
                        $campaignRecipientListRecord->campaignId = $campaignRecord->id;
                        $campaignRecipientListRecord->save ( false );
                    }
                }
            }
        }
        
        // now we need to disassociate recipient lists as needed
        foreach ( $campaignRecord->recipientList as $list ) 
        {
            // was part of campaign, but now isn't
            if (! in_array ( $list->emailProviderRecipientListId, $recipientListIds )) 
            {
                craft ()->sproutEmail->deleteCampaignRecipientList ( $list->id, $campaignRecord->id );
            }
        }

	    
		$recipientIds = array();
			
		// parse and create individual recipients as needed
		$recipients = array_filter(explode(",", $campaign->recipients));
		if( ! $campaign->useRecipientLists && ! $recipients)
		{
			$campaign->addError('recipients', 'You must add at least one valid email.');
			return false;
		}
		
		if($campaign->useRecipientLists && ! $recipientListIds)
		{
		    $campaign->addError('recipients', 'You must add at least one valid email or select an email list.');
		    return false;
		}
	
		// validate emails
		$trimmed_recipient_list = array();
		foreach($recipients as $email)
		{		    
		    $email = trim($email);
			$recipientRecord = new SproutEmail_RecipientRecord();	
			$recipientRecord->email = $email;

			if(! preg_match('/{{(.*?)}}/', $email))
			{
				$recipientRecord->validate();
    			if($recipientRecord->hasErrors())
    			{
    				$campaign->addError('recipients', 'Once or more of listed emails are not valid.');
    				return false;
    			};
			}
			
			$trimmed_recipient_list[] = $email;
		}
	
		$campaignRecord->recipients = implode(",", $trimmed_recipient_list);
		$campaignRecord->save();
		
		$this->cleanUpRecipientListOrphans($campaignRecord);
		
		return true;
	}
	
	/**
	 * Deletes recipients for specified campaign
	 * 
	 * @param SproutEmail_CampaignRecord $campaignRecord
	 * @return bool
	 */
	public function deleteRecipients(SproutEmail_CampaignRecord $campaignRecord)
	{
		// no external recipient list for this service
		return true;
	}
	
	public function getSettings()
	{
	    $obj = new \StdClass();
	    $obj->valid = true;
	    return $obj;
	}
	
	public function saveSettings($settings = array())
	{
	    //
	}
}
