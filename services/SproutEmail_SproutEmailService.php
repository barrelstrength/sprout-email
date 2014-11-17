<?php
namespace Craft;

/**
 * SproutEmail service
 */
class SproutEmail_SproutEmailService extends SproutEmail_EmailProviderService implements SproutEmail_EmailProviderInterfaceService
{
	/**
	 * The SproutEmail service supports two types of subscriber lists:
	 * Craft groups
	 * SproutReports
	 *
	 * @return multitype: multitype:NULL
	 */
	public function getSubscriberList()
	{
		$options = array ();
		
		// Craft groups
		if ( $userGroups = craft()->userGroups->getAllGroups() )
		{
			$options['UserGroup']['label']       = Craft::t( 'Member Groups' );
			$options['UserGroup']['description'] = Craft::t( 'Select one or more member groups to send your Entry.' );
			
			foreach ( $userGroups as $userGroup )
			{
				$options['UserGroup']['options'][$userGroup->id] = $userGroup->name . Craft::t( ' [Craft user group]' );
			}
		}
		
		// SproutReports
		if ( $reports = craft()->plugins->getPlugin( 'sproutreports' ) )
		{
			if ( $list = craft()->sproutReports_reports->getAllReportsByAttributes(array(
				'isEmailList' => 1 
			)))
			{
				$options['SproutReport']['label'] = Craft::t( 'Sprout Reports Email Lists' );
				$options['SproutReport']['description'] = Craft::t( 'Select one or more email lists to send your Entry.' );
				
				foreach ( $list as $report )
				{
					$options['SproutReport']['options'][$report->id] = $report->name . Craft::t( ' [SproutReport]' );
				}
			}
		}
		
		// Other elements
		// @TODO - Consider updating this to just grab Elements that add support via a Hook
		// Many Elements will just be clutter here so might as well make it opt-in
		$elementTypes = craft()->elements->getAllElementTypes();
		
		$ignore = array (
			'Asset',
			'GlobalSet',
			'MatrixBlock',
		);
		
		foreach ( $elementTypes as $key => $elementType )
		{
			if ( in_array( $key, $ignore ) )
				continue;
			
			$criteria = craft()->elements->getCriteria( $key );
			if ( $results = craft()->elements->findElements( $criteria ) )
			{
				foreach ( $results as $row )
				{
					if ( ! isset( $options [$key] ['label'] ) )
					{
						$options [$key] ['label'] = Craft::t( $key . ' Subscriber Lists' );
						$options [$key] ['description'] = Craft::t( 'Select one or more ' . strtolower($key) . ' subscriber lists to send your Entry.' );
					}
					
					$options [$key] ['options'] [$row->id] = ( string ) $row;
				}
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
	public function exportEntry($campaign = array(), $listIds = array(), $return = false)
	{
		$campaignModel = craft()->sproutEmail_campaign->getCampaignById($campaign['id']);
		
		if ( $this->sendEntry( $campaignModel, $listIds ) === false )
		{
			if ( $return )
			{
				return false;
			}
			die( 'Your campaign can not be sent at this time.' );
		}
		if ( $return )
		{
			return true;
		}
		die( 'Campaign successfully sent.' );
	}
	
	/**
	 * Exports campaign (with send)
	 *
	 * @param array $campaign            
	 * @param array $listIds            
	 */
	public function sendEntry($campaign = array(), $listIds = array())
	{
		// @TODO - do we need to udpate textBody here?
		$emailData = array (
			'fromEmail' => $campaign->fromEmail,
			'fromName' => $campaign->fromName,
			'subject' => $campaign->subject,
			'body' => $campaign->textBody 
		);
		
		// @TODO - do we need to udpate htmlBody here?
		if ( $campaign->htmlBody )
		{
			$emailData ['htmlBody'] = $campaign->htmlBody;
		}
		
		// since we're allowing unchecked variables as replyTo, let's make sure it's a valid email before adding
		if ( $campaign->replyToEmail && preg_match( "/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $campaign->replyToEmail ) )
		{
			$emailData ['replyTo'] = $campaign->replyToEmail;
		}
		
		$recipients = explode( ",", $campaign->recipients );
		
		// stash our user groups for easy referencing
		$userGroupsArr = array ();
		if ( $userGroups = craft()->userGroups->getAllGroups() )
		{
			foreach ( $userGroups as $userGroup )
			{
				$userGroupsArr [$userGroup->id] = $userGroup;
			}
		}
		
		if ( $recipientLists = craft()->sproutEmail_campaign->getCampaignRecipientLists( $campaign ['id'] ) )
		{
			foreach ( $recipientLists as $recipientList )
			{
				switch ($recipientList->type)
				{
					case 'UserGroup' :
						$criteria = craft()->elements->getCriteria( 'User' );
						$criteria->groupId = $userGroupsArr [$recipientList->emailProviderRecipientListId]->id;
						if ( $results = craft()->elements->findElements( $criteria ) )
						{
							foreach ( $results as $row )
							{
								$recipients [] = $row->email;
							}
						}
						break;

					case 'SproutReport' :
						if ( $report = craft()->sproutReports_reports->getReportById( $recipientList->emailProviderRecipientListId ) )
						{
							$results = craft()->sproutReports_reports->runReport( $report ['customQuery'] );
							foreach ( $results as $row )
							{
								if ( isset( $row ['email'] ) )
								{
									$recipients [] = $row ['email'];
								}
							}
						}
						break;

					// Elements
					default :
						if ( $results = craft()->sproutEmail_subscriptions->getSubscriptionUsersByElementId( $recipientList->emailProviderRecipientListId ) )
						{
							foreach ( $results as $result )
							{
								foreach ( $result as $user )
								{
									$recipients [] = $user->email;
								}
							}
						}
						break;
				}
			}
		}
		
		// remove duplicates & blanks
		$recipients = array_unique( array_filter( $recipients ) );
		
		$emailModel = EmailModel::populateModel( $emailData );
		
		$post = craft()->request->getPost();
		foreach ( $recipients as $recipient )
		{
			try
			{
				$emailModel->toEmail = craft()->templates->renderString($recipient, array(
						'entry' => $post 
				));

				craft()->email->sendEmail($emailModel);
			}
			catch (\Exception $e)
			{
				// do nothing
				return false;
			}
		}
	}
	
	/**
	 * Save local recipient list
	 *
	 * @param object $campaign            
	 * @param object $campaignRecord            
	 * @return boolean
	 */
	public function saveRecipientList(SproutEmail_CampaignModel &$campaign, SproutEmail_CampaignRecord &$campaignRecord)
	{
		// an email provider is required
		if ( ! isset( $campaign->emailProvider ) || ! $campaign->emailProvider )
		{
			$campaign->addError( 'emailProvider', 'Unsupported email provider.' );
			return false;
		}
		
		// if we have what we need up to this point,
		// get the recipient list(s) by emailProvider and emailProviderRecipientListId
		$criteria = new \CDbCriteria();
		$criteria->condition = 'emailProvider=:emailProvider';
		$criteria->params = array (
				':emailProvider' => $campaign->emailProvider 
		);
		
		$currentCampaignRecipientLists = $campaignRecord->recipientList;
		
		if ( $recipientListGroups = array_filter( ( array ) $campaign->emailProviderRecipientListId ) )
		{	
			// process each recipient listCampaigns
			foreach ( $recipientListGroups as $groupType => $recipientListIds )
			{
				if ( ! $recipientListIds )
					continue;
				
				foreach ( $recipientListIds as $list_id )
				{
					$criteria->condition = 'emailProviderRecipientListId=:emailProviderRecipientListId AND type=:type';
					$criteria->params = array (
							':emailProviderRecipientListId' => $list_id,
							':type' => $groupType 
					);
					$recipientListRecord = SproutEmail_RecipientListRecord::model()->find( $criteria );
					
					if ( ! $recipientListRecord ) // doesn't exist yet, so we need to create
					{
						// save record
						$recipientListRecord = new SproutEmail_RecipientListRecord();
						$recipientListRecord->emailProviderRecipientListId = $list_id;
						$recipientListRecord->type = $groupType;
						$recipientListRecord->emailProvider = $campaign->emailProvider;
					}
					
					// we already did our validation, so just save
					if ( $recipientListRecord->save() )
					{
						// associate with campaign, if not already done so
						if ( SproutEmail_CampaignRecipientListRecord::model()->count( 'recipientListId=:recipientListId AND campaignId=:campaignId', array (
								':recipientListId' => $recipientListRecord->id,
								':campaignId' => $campaignRecord->id 
						) ) == 0 )
						{
							$campaignRecipientListRecord = new SproutEmail_CampaignRecipientListRecord();
							$campaignRecipientListRecord->recipientListId = $recipientListRecord->id;
							$campaignRecipientListRecord->campaignId = $campaignRecord->id;
							$campaignRecipientListRecord->save( false );
						}
					}
				}
			}
		}
		
		// now we need to disassociate recipient lists as needed
		foreach ( $currentCampaignRecipientLists as $list )
		{
			// check against the model
			if ( ! $campaign->hasRecipientList( $list ) )
			{
				craft()->sproutEmail_campaign->deleteCampaignRecipientList( $list->id, $campaignRecord->id );
			}
		}
		
		$recipientIds = array ();
		
		// parse and create individual recipients as needed
		$recipients = array_filter( explode( ",", $campaign->recipients ) );

		if ( ! $campaign->useRecipientLists && ! $recipients )
		{
			$campaign->addError( 'recipients', 'You must add at least one valid email.' );
			return false;
		}
		
		if ( $campaign->useRecipientLists && ! isset( $recipientListIds ) )
		{
			$campaign->addError( 'recipients', 'You must add at least one valid email or select an email list.' );
			return false;
		}
		
		// validate emails
		$trimmed_recipient_list = array ();
		foreach ( $recipients as $email )
		{
			$email = trim( $email );
			
			if ( ! preg_match( '/{{(.*?)}}/', $email ) )
			{
				$recipientModel = SproutEmail_RecipientModel::populateModel( array (
						'email' => $email 
				) );
				if ( ! $recipientModel->validate() )
				{
					$campaign->addError( 'recipients', 'Once or more of listed emails are not valid.' );
					return false;
				}
				;
			}
			
			$trimmed_recipient_list [] = $email;
		}
		
		$campaignRecord->recipients = implode( ",", $trimmed_recipient_list );
		$campaignRecord->save();
		
		$this->cleanUpRecipientListOrphans( $campaignRecord );
		
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
		$success = true;
		if ( $campaignRecord->recipientList )
		{
			foreach ( $campaignRecord->recipientList as $list )
			{
				if ( ! craft()->sproutEmail_campaign->deleteCampaignRecipientList( $list->id, $campaignRecord->id ) )
				{
					$success = false;
				}
			}
			
			$this->cleanUpRecipientListOrphans( $campaignRecord );
		}
		
		return $success;
	}
	
	/**
	 *
	 * @return \StdClass
	 */
	public function getSettings()
	{
		$obj = new \StdClass();
		$obj->valid = true;
		return $obj;
	}
	
	/**
	 *
	 * @param array $settings            
	 */
	public function saveSettings($settings = array())
	{
		//
	}
}
