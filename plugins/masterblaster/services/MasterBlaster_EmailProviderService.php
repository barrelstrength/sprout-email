<?php
namespace Craft;

/**
 * Base email provider service
 */
class MasterBlaster_EmailProviderService extends BaseApplicationComponent
{
	/**
	 * Returns supported email providers based on which libraries
	 * are installed in /masterblaster/libraries
	 * @return multitype:string NULL
	 */
	public function getEmailProviders()
	{
		$plugins_path = rtrim(craft()->path->getPluginsPath(), '\\/');
		$files = scandir($plugins_path . '/masterblaster/libraries');
		$select_options = array('masterblaster' => 'Master Blaster');
			
		// set <select> values and text to be the same
		foreach($files as $file)
		{
			if ($file !== '.' && $file !== '..')
			{
				$select_options[$file] = ucwords(str_replace('_', ' ', $file));
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
	public function exportCampaign($entryId, $campaignId)
	{
		if( ! $campaign = craft()->masterBlaster->getSectionBasedCampaignByEntryAndCampaignId($entryId, $campaignId))
		{
			die('Campaign not found');
		}
		
		if($campaign['emailProvider'] == 'masterblaster')
		{
			craft()->masterBlaster_masterblaster->exportCampaign(
				craft()->request->getPost('entryId'),craft()->request->getPost('campaignId'));
		}

		if( ! $recipientLists = craft()->masterBlaster->getCampaignRecipientLists($campaign['id']))
		{
			die('Recipient lists not found');
		}

		$listIds = array();
		$listProviders = array();
		foreach($recipientLists as $list)
		{
			$listProviders[] = $list->emailProvider;
			$listIds[$list->emailProvider][] = $list['emailProviderRecipientListId'];
		}
		
		foreach($listProviders as $provider)
		{
			$provider_service = 'masterBlaster_' . $provider;
			craft()->{$provider_service}->exportCampaign($campaign, $listIds[$provider]);
		}
		die();
	}

	/**
	 * Saves recipient list for campaign
	 *
	 * @param MasterBlaster_CampaignModel $campaign
	 * @param MasterBlaster_CampaignRecord $campaignRecord
	 * @throws Exception
	 * @return void
	 */
	public function saveRecipientList(MasterBlaster_CampaignModel &$campaign, MasterBlaster_CampaignRecord &$campaignRecord)
	{
		// an email provider is required
		if( ! isset($campaign->emailProvider) || ! $campaign->emailProvider)
		{
			$campaign->addError('emailProvider', 'Unsupported email provider.');
		}
	
		// at least one recipient list is required
		if( ! isset($campaign->emailProviderRecipientListId) || ! $campaign->emailProviderRecipientListId)
		{
			$campaign->addError('emailProviderRecipientListId', 'You must select at least one recipient list.');
		}
	
		// if we have what we need up to this point,
		// get the recipient list(s) by emailProvider and emailProviderRecipientListId
		if ( ! $campaign->hasErrors())
		{
			$criteria = new \CDbCriteria();
			$criteria->condition = 'emailProvider=:emailProvider';
			$criteria->params = array(':emailProvider' => $campaign->emailProvider);
				
			$recipientListIds = (array) $campaign->emailProviderRecipientListId;
	
			// process each recipient listCampaigns
			foreach($recipientListIds as $list_id)
			{
				$criteria->condition = 'emailProviderRecipientListId=:emailProviderRecipientListId';
				$criteria->params = array(':emailProviderRecipientListId' => $list_id);
				$recipientListRecord = MasterBlaster_RecipientListRecord::model()->find($criteria);
	
				if( ! $recipientListRecord) // doesn't exist yet, so we need to create
				{
					// TODO: for now, we'll assume that the email provider's recipient list id is valid,
					// but ideally, we'd want to check with the api to make sure that it is in fact valid
	
					// save record
					$recipientListRecord = new MasterBlaster_RecipientListRecord();
					$recipientListRecord->emailProviderRecipientListId = $list_id;
					$recipientListRecord->emailProvider = $campaign->emailProvider;
				}
	
				// we already did our validation, so just save
				if( $recipientListRecord->save())
				{
				// associate with campaign, if not already done so
					if( MasterBlaster_CampaignRecipientListRecord::model()
					->count('recipientListId=:recipientListId AND campaignId=:campaignId', array(
							':recipientListId' => $recipientListRecord->id,
							':campaignId' => $campaignRecord->id)) == 0)
					{
						$campaignRecipientListRecord = new MasterBlaster_CampaignRecipientListRecord();
						$campaignRecipientListRecord->recipientListId = $recipientListRecord->id;
						$campaignRecipientListRecord->campaignId = $campaignRecord->id;
						$campaignRecipientListRecord->save(false);
					}
				}
			}
	
			// now we need to disassociate recipient lists as needed
			foreach($campaignRecord->recipientList as $list)
			{
				// was part of campaign, but now isn't
				if( ! in_array($list->emailProviderRecipientListId, $recipientListIds))
				{
					craft()->masterBlaster->deleteCampaignRecipientList($list->id, $campaignRecord->id);
				}
			}
		}
		else 
		{
			return false;
		}
		
		$this->cleanUpRecipientListOrphans($campaignRecord);
		
		return true;
	}
	
	/**
	 * Deletes recipients for specified campaign
	 *
	 * @param MasterBlaster_CampaignRecord $campaignRecord
	 * @return bool
	 */
	public function deleteRecipients(MasterBlaster_CampaignRecord $campaignRecord)
	{
		// delete associated recipient lists
		if( ! craft()->db->createCommand()->delete('masterblaster_campaign_recipient_lists', array('campaignId' => $campaignRecord->id)))
		{
			return false;
		}
	
		if( ! $this->cleanUpRecipientListOrphans($campaignRecord))
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
		return craft()->db->createCommand()->delete('masterblaster_recipient_lists', array('id' => $recipientListId));
	}
	
	/**
	 * Cleans up the recipient list after saving
	 *
	 * @param MasterBlaster_CampaignRecord $campaignRecord
	 * @return boolean
	 */
	public function cleanUpRecipientListOrphans(&$campaignRecord)
	{
		// clean up recipient lists if orphaned
		if( ! empty($campaignRecord->recipientList))
		{
			// first let's prep our data
			$recipientListIds = array();
			foreach($campaignRecord->recipientList as $list)
			{
				$recipientListIds[] = $list->id;
			}
	
			// now check if there are any orphans
			$criteria = new \CDbCriteria();
			$criteria->addInCondition('t.id', $recipientListIds);
			$criteria->condition = 'campaign.id is null';
	
			$orphans = MasterBlaster_RecipientListRecord::model()
			->with('campaign')
			->findAll($criteria);
	
			if($orphans)
			{
				foreach($orphans as $recipientList)
				{
					if( ! $this->deleteRecipientList($recipientList->id))
					{
						return false;
					}
				}
			}
		}
		return true;
	}
}
