<?php
namespace Craft;

/**
 * Base email provider service
 */
class SproutEmail_EmailProviderService extends BaseApplicationComponent
{
	/**
	 * Returns supported email providers based on which libraries
	 * are installed in /sproutemail/libraries
	 *
	 * @return multitype:string NULL
	 */
	public function getEmailProviders()
	{
		$plugins_path = rtrim( craft()->path->getPluginsPath(), '\\/' );
		$files = scandir( $plugins_path . '/sproutemail/libraries' );
		$select_options = array (
			'SproutEmail' => 'SproutEmail' 
		);
		// $select_options = array();
		
		// set <select> values and text to be the same
		foreach ( $files as $file )
		{
			if ( $file !== '.' && $file !== '..' )
			{
				$select_options [$file] = ucwords( str_replace( '_', ' ', $file ) );
			}
		}
		
		return $select_options;
	}
	
	/**
	 * Base function for exporting section based emails
	 *
	 * @param int $entryId            
	 * @param int $campaignId            
	 */
	public function exportEntry($entryId, $campaignId, $return = false)
	{
		// Define our variables
		$listIds = array ();
		$listProviders = array ();
		
		// Get our campaign info
		if ( ! $campaign = craft()->sproutEmail_campaign->getCampaignById($campaignId) )
		{
			SproutEmailPlugin::log("Campaign not found");

			if ( $return )
			{
				return false;
			}

			// @TODO - update use of die
			die( 'Campaign not found' );
		}

		// Get our recipient list info
		if($campaign["emailProvider"] != 'CopyPaste')
		{
			if ( ! $recipientLists = craft()->sproutEmail_campaign->getCampaignRecipientLists( $campaign ['id'] ) )
			{
				SproutEmailPlugin::log("Recipient lists not found");

				if ( $return )
				{
					return false;
				}

				// @TODO - update use of die
				die( 'Recipient lists not found' );
			}
		}
		else
		{
			SproutEmailPlugin::log("Exporting Copy/Paste Email");

			// We can't check for a recipients list on CopyPaste since one doesn't exist
			craft()->sproutEmail_copyPaste->exportEntry( $campaign );

			// @TODO - update use of die
			die();
		}
		
		// Check to see if we have entry level settings and update 
		// before shuffling off to the individual service connectors. 

		$entry = craft()->entries->getEntryById($entryId);
		$entryFields = $entry->getFieldLayout()->getFields();

		// Assume we have no override, and update overrideHandle if we do
		$overrideHandle = "";

		foreach ($entryFields as $field) 
		{
			// If we have an Email Campaign Field, grab the handle of the first one that matches
			if ($field->getField()->type == 'SproutEmail_EmailCampaign')
			{
				SproutEmailPlugin::log('We have a Campaign Override field');
				SproutEmailPlugin::log('Override Field Handle: ' . $field->getField()->handle);

				$overrideHandle = $field->getField()->handle;
				continue;
			}
		}
	
		// If the entry has an override handle assigned to it
		if ($overrideHandle != "")
		{
			// Grab our Email Campaign override settings
			$entryOverrideSettings = $entry->{$overrideHandle};
			$entryOverrideSettings = json_decode($entryOverrideSettings,TRUE);
			
			SproutEmailPlugin::log('Our override settings: ' . $entry->{$overrideHandle});

			// Merge the entry level settings with our campaign
			$emailProviderRecipientListId = '';

			if( isset($entryOverrideSettings) )
			{
				foreach($entryOverrideSettings as $key => $value)
				{
					// Override our campaign settings
					$campaign[$key] = $value;

					if($key == 'emailProviderRecipientListId')
					{
						// Make sure our $emailProviderRecipientListId is an array
						// @TODO - clarify what this value is for.  Is it only for Mailgun?
						$emailProviderRecipientListId = (array) $value;
						$campaign[$key] = $emailProviderRecipientListId;
					}

					SproutEmailPlugin::log('Entry override ' . $key . ': ' . $value);
					
				}
			}
			
			// @TODO - need to revisit this behavior big time!
			if($emailProviderRecipientListId != '')
			{	
				foreach ($emailProviderRecipientListId as $key => $value) 
				{
					$recipientLists[0]->emailProviderRecipientListId = $entryOverrideSettings['emailProviderRecipientListId']['list'];
					$recipientLists[0]->emailProvider = $campaign['emailProvider'];
					$recipientLists[0]->type = null;

					// $recipientListOverrides[$key] = $recipientListOverride;
				}
			}
		}

		// Create the recipient list variables
		foreach ( $recipientLists as $list )
		{
			$listProviders [] = $list->emailProvider;
			$listIds [$list->emailProvider] [] = $list ['emailProviderRecipientListId'];
		}

		foreach ( $listProviders as $provider )
		{
			$provider_service = 'sproutEmail_' . lcfirst( $provider );
			craft()->{$provider_service}->exportEntry( $campaign, $listIds [$provider], $return );
		}
		
		if ( $return )
		{
			return true;
		}

		// @TODO - update use of die
		die();
	}
	
	/**
	 * Saves recipient list for campaign
	 *
	 * @param SproutEmail_CampaignModel $campaign            
	 * @param SproutEmail_CampaignRecord $campaignRecord            
	 * @throws Exception
	 * @return void
	 */
	public function saveRecipientList(SproutEmail_CampaignModel &$campaign, SproutEmail_CampaignRecord &$campaignRecord)
	{
		// an email provider is required
		if ( ! isset( $campaign->emailProvider ) || ! $campaign->emailProvider )
		{
			$campaign->addError( 'emailProvider', 'Unsupported email provider.' );
		}
		
		// at least one recipient list is required
		if ( ! isset( $campaign->emailProviderRecipientListId ) || ! $campaign->emailProviderRecipientListId )
		{
			$campaign->addError( 'emailProviderRecipientListId', 'You must select at least one recipient list.' );
		}
		
		// if we have what we need up to this point,
		// get the recipient list(s) by emailProvider and emailProviderRecipientListId
		if ( ! $campaign->hasErrors() )
		{
			$criteria = new \CDbCriteria();
			$criteria->condition = 'emailProvider=:emailProvider';
			$criteria->params = array (
				':emailProvider' => $campaign->emailProvider 
			);
			
			$recipientListIds = ( array ) $campaign->emailProviderRecipientListId;
			
			// process each recipient listCampaigns
			foreach ( $recipientListIds as $list_id )
			{
				$criteria->condition = 'emailProviderRecipientListId=:emailProviderRecipientListId';
				$criteria->params = array (
					':emailProviderRecipientListId' => $list_id 
				);
				$recipientListRecord = SproutEmail_RecipientListRecord::model()->find( $criteria );
				
				if ( ! $recipientListRecord ) // doesn't exist yet, so we need to create
				{
					// TODO: for now, we'll assume that the email provider's recipient list id is valid,
					// but ideally, we'd want to check with the api to make sure that it is in fact valid
					
					// save record
					$recipientListRecord = new SproutEmail_RecipientListRecord();
					$recipientListRecord->emailProviderRecipientListId = $list_id;
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
			
			// now we need to disassociate recipient lists as needed
			foreach ( $campaignRecord->recipientList as $list )
			{
				// was part of campaign, but now isn't
				if ( ! in_array( $list->emailProviderRecipientListId, $recipientListIds ) )
				{
					craft()->sproutEmail_campaign->deleteCampaignRecipientList( $list->id, $campaignRecord->id );
				}
			}
		}
		else
		{
			return false;
		}
		
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
		// delete associated recipient lists
		if ( ! craft()->db->createCommand()->delete( 'sproutemail_campaigns_recipientlists', array (
				'campaignId' => $campaignRecord->id 
		) ) )
		{
			return false;
		}
		
		if ( ! $this->cleanUpRecipientListOrphans( $campaignRecord ) )
		{
			return false;
		}
		
		return true;
	}
	
	/**
	 * Delete recipient list
	 *
	 * @param int $recipientListId            
	 * @return bool
	 */
	public function deleteRecipientList($recipientListId)
	{
		return craft()->db->createCommand()->delete( 'sproutemail_recipientlists', array (
				'id' => $recipientListId 
		) );
	}
	
	/**
	 * Cleans up the recipient list after saving
	 *
	 * @param SproutEmail_CampaignRecord $campaignRecord            
	 * @return boolean
	 */
	public function cleanUpRecipientListOrphans(&$campaignRecord)
	{
		// clean up recipient lists if orphaned
		if ( ! empty( $campaignRecord->recipientList ) )
		{
			// first let's prep our data
			$recipientListIds = array ();
			foreach ( $campaignRecord->recipientList as $list )
			{
				$recipientListIds [] = $list->id;
			}
			
			// now check if there are any orphans
			$criteria = new \CDbCriteria();
			$criteria->addInCondition( 't.id', $recipientListIds );
			$criteria->condition = 'campaign.id is null';
			
			$orphans = SproutEmail_RecipientListRecord::model()->with( 'campaign' )->findAll( $criteria );
			
			if ( $orphans )
			{
				foreach ( $orphans as $recipientList )
				{
					if ( ! $this->deleteRecipientList( $recipientList->id ) )
					{
						return false;
					}
				}
			}
		}
		return true;
	}
}
