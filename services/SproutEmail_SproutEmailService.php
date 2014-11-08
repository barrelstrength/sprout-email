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
			$options ['UserGroup'] ['label'] = Craft::t( 'Member Groups' );
			$options ['UserGroup'] ['description'] = Craft::t( 'Select one or more member groups to send your email blast.' );
			
			foreach ( $userGroups as $userGroup )
			{
				$options ['UserGroup'] ['options'] [$userGroup->id] = $userGroup->name . Craft::t( ' [Craft user group]' );
			}
		}
		
		// SproutReports
		if ( $reports = craft()->plugins->getPlugin( 'sproutreports' ) )
		{
			if ( $list = craft()->sproutReports_reports->getAllReportsByAttributes( array (
					'isEmailList' => 1 
			) ) )
			{
				$options ['SproutReport'] ['label'] = Craft::t( 'Sprout Reports Email Lists' );
				$options ['SproutReport'] ['description'] = Craft::t( 'Select one or more email lists to send your email blast.' );
				
				foreach ( $list as $report )
				{
					$options ['SproutReport'] ['options'] [$report->id] = $report->name . Craft::t( ' [SproutReport]' );
				}
			}
		}
		
		// Other elements
		$elementTypes = craft()->elements->getAllElementTypes();
		$ignore = array (
				'SproutWorms_Form',
				'SproutWorms_Entry' 
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
						$options [$key] ['description'] = Craft::t( 'Select one or more ' . strtolower($key) . ' subscriber lists to send your email blast.' );
					}
					
					$options [$key] ['options'] [$row->id] = ( string ) $row;
				}
			}
		}
		
		return $options;
	}
	
	/**
	 * Exports emailBlastType (no send)
	 *
	 * @param array $emailBlastType            
	 * @param array $listIds            
	 */
	public function exportEmailBlast($emailBlastType = array(), $listIds = array(), $return = false)
	{
		$emailBlastTypeModel = craft()->sproutEmail->getEmailBlastType( array (
				'id' => $emailBlastType ['id'] 
		) );
		
		if ( $this->sendEmailBlast( $emailBlastTypeModel, $listIds ) === false )
		{
			if ( $return )
			{
				return false;
			}
			die( 'Your emailBlastType can not be sent at this time.' );
		}
		if ( $return )
		{
			return true;
		}
		die( 'EmailBlastType successfully sent.' );
	}
	
	/**
	 * Exports emailBlastType (with send)
	 *
	 * @param array $emailBlastType            
	 * @param array $listIds            
	 */
	public function sendEmailBlast($emailBlastType = array(), $listIds = array())
	{
		// @TODO - do we need to udpate textBody here?
		$emailData = array (
				'fromEmail' => $emailBlastType->fromEmail,
				'fromName' => $emailBlastType->fromName,
				'subject' => $emailBlastType->subject,
				'body' => $emailBlastType->textBody 
		);
		
		// @TODO - do we need to udpate htmlBody here?
		if ( $emailBlastType->htmlBody )
		{
			$emailData ['htmlBody'] = $emailBlastType->htmlBody;
		}
		
		// since we're allowing unchecked variables as replyTo, let's make sure it's a valid email before adding
		if ( $emailBlastType->replyToEmail && preg_match( "/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $emailBlastType->replyToEmail ) )
		{
			$emailData ['replyTo'] = $emailBlastType->replyToEmail;
		}
		
		$recipients = explode( ",", $emailBlastType->recipients );
		
		// stash our user groups for easy referencing
		$userGroupsArr = array ();
		if ( $userGroups = craft()->userGroups->getAllGroups() )
		{
			foreach ( $userGroups as $userGroup )
			{
				$userGroupsArr [$userGroup->id] = $userGroup;
			}
		}
		
		if ( $recipientLists = craft()->sproutEmail->getEmailBlastTypeRecipientLists( $emailBlastType ['id'] ) )
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
					default : // element
						if ( $results = craft()->sproutEmail->getSubscriptionUsersByElementId( $recipientList->emailProviderRecipientListId ) )
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
		
		// Craft::dump($emailData);Craft::dump($recipients);die('<br/>To disable test mode and send emails, remove line 57 in ' . __FILE__);
		$emailModel = EmailModel::populateModel( $emailData );
		
		$post = craft()->request->getPost();
		foreach ( $recipients as $recipient )
		{
			try
			{
				$emailModel->toEmail = craft()->templates->renderString( $recipient, array (
						'entry' => $post 
				) );
				craft()->email->sendEmail( $emailModel );
			}
			catch ( \Exception $e )
			{
				// do nothing
				return false;
			}
		}
	}
	
	/**
	 * Save local recipient list
	 *
	 * @param object $emailBlastType            
	 * @param object $emailBlastTypeRecord            
	 * @return boolean
	 */
	public function saveRecipientList(SproutEmail_EmailBlastTypeModel &$emailBlastType, SproutEmail_EmailBlastTypeRecord &$emailBlastTypeRecord)
	{
		// an email provider is required
		if ( ! isset( $emailBlastType->emailProvider ) || ! $emailBlastType->emailProvider )
		{
			$emailBlastType->addError( 'emailProvider', 'Unsupported email provider.' );
			return false;
		}
		
		// if we have what we need up to this point,
		// get the recipient list(s) by emailProvider and emailProviderRecipientListId
		$criteria = new \CDbCriteria();
		$criteria->condition = 'emailProvider=:emailProvider';
		$criteria->params = array (
				':emailProvider' => $emailBlastType->emailProvider 
		);
		
		$currentEmailBlastTypeRecipientLists = $emailBlastTypeRecord->recipientList;
		
		if ( $recipientListGroups = array_filter( ( array ) $emailBlastType->emailProviderRecipientListId ) )
		{	
			// process each recipient listEmailBlastTypes
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
						$recipientListRecord->emailProvider = $emailBlastType->emailProvider;
					}
					
					// we already did our validation, so just save
					if ( $recipientListRecord->save() )
					{
						// associate with emailBlastType, if not already done so
						if ( SproutEmail_EmailBlastTypeRecipientListRecord::model()->count( 'recipientListId=:recipientListId AND emailBlastTypeId=:emailBlastTypeId', array (
								':recipientListId' => $recipientListRecord->id,
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
		}
		
		// now we need to disassociate recipient lists as needed
		foreach ( $currentEmailBlastTypeRecipientLists as $list )
		{
			// check against the model
			if ( ! $emailBlastType->hasRecipientList( $list ) )
			{
				craft()->sproutEmail->deleteEmailBlastTypeRecipientList( $list->id, $emailBlastTypeRecord->id );
			}
		}
		
		$recipientIds = array ();
		
		// parse and create individual recipients as needed
		$recipients = array_filter( explode( ",", $emailBlastType->recipients ) );
		if ( ! $emailBlastType->useRecipientLists && ! $recipients )
		{
			$emailBlastType->addError( 'recipients', 'You must add at least one valid email.' );
			return false;
		}
		
		if ( $emailBlastType->useRecipientLists && ! isset( $recipientListIds ) )
		{
			$emailBlastType->addError( 'recipients', 'You must add at least one valid email or select an email list.' );
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
					$emailBlastType->addError( 'recipients', 'Once or more of listed emails are not valid.' );
					return false;
				}
				;
			}
			
			$trimmed_recipient_list [] = $email;
		}
		
		$emailBlastTypeRecord->recipients = implode( ",", $trimmed_recipient_list );
		$emailBlastTypeRecord->save();
		
		$this->cleanUpRecipientListOrphans( $emailBlastTypeRecord );
		
		return true;
	}
	
	/**
	 * Deletes recipients for specified emailBlastType
	 *
	 * @param SproutEmail_EmailBlastTypeRecord $emailBlastTypeRecord            
	 * @return bool
	 */
	public function deleteRecipients(SproutEmail_EmailBlastTypeRecord $emailBlastTypeRecord)
	{
		$success = true;
		if ( $emailBlastTypeRecord->recipientList )
		{
			foreach ( $emailBlastTypeRecord->recipientList as $list )
			{
				if ( ! craft()->sproutEmail->deleteEmailBlastTypeRecipientList( $list->id, $emailBlastTypeRecord->id ) )
				{
					$success = false;
				}
			}
			
			$this->cleanUpRecipientListOrphans( $emailBlastTypeRecord );
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
