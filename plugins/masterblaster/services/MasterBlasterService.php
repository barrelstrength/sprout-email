<?php
namespace Craft;

/**
 * Main MasterBlaster service
 */
class MasterBlasterService extends BaseApplicationComponent
{
	/**
	 * Returns all campaigns.
	 *
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getAllCampaigns()
	{
		$criteria = new \CDbCriteria();
		$criteria->order = 'dateCreated DESC';
		return MasterBlaster_CampaignRecord::model()->findAll($criteria);
	}
	
	/**
	 * Returns all campaignRecipeint lists
	 * @param int $campaignId
	 */
	public function getCampaignRecipientLists($campaignId)
	{
		$criteria = new \CDbCriteria();
		$criteria->condition = 'campaign.id=:campaignId';
		$criteria->params = array(':campaignId' => $campaignId);
		
		return MasterBlaster_RecipientListRecord::model()
		->with('campaign')
		->findAll($criteria);
	}
	
	/**
	 * Returns all section based campaigns.
	 *
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getSectionCampaigns($campaign_id = false)
	{
		return MasterBlaster_CampaignRecord::model()->getSectionBasedCampaigns($campaign_id);
	}
	
	/**
	 * Returns section based campaign by entryId
	 *
	 * @param string|null $entryId
	 * @return array
	 */
	public function getSectionBasedCampaignByEntryAndCampaignId($entryId = false, $campaignId = false)
	{
		return MasterBlaster_CampaignRecord::model()->getSectionBasedCampaignByEntryAndCampaignId($entryId, $campaignId);
	}

	/**
	 * Gets a campaign
	 *
	 * @param array possible conditions: array('id' => <id>, 'handle' => <handle>, ...) as defined in $valid_keys
	 * @return MasterBlaster_CampaignModel|null
	 */
	public function getCampaign($conditions = array())
	{
		$criteria = new \CDbCriteria();
		$criteria->condition = 'campaignId=:campaignId';
		$criteria->params = array(':campaignId' => $conditions['id']);
		$campaignRecord = MasterBlaster_CampaignRecord::model()
		->with('recipientList')
		->find($criteria);
		
		// we can do where clauses on these keys only
		$valid_keys = array('id', 'handle');
		
		$criteria = new \CDbCriteria();

		if( ! empty($conditions))
		{
			$params = array();
			foreach($conditions as $key => $val)
			{
				if( ! in_array($key, $valid_keys)) // we only accept our defined keys
				{
					continue;
				}
				
				$criteria->addCondition('t.' . $key . '=:' . $key);
				$params[':' . $key] = $val;
			}
			
			if( ! empty($params))
			{
				$criteria->params = $params;
			}
		}
				
		// get campaign record with recipient lists
		$campaignRecord = MasterBlaster_CampaignRecord::model()
		->with('recipientList','campaignNotificationEvent')
		->find($criteria);

		if ($campaignRecord)
		{
			// now we need to populate the model
			$campaignModel = MasterBlaster_CampaignModel::populateModel($campaignRecord);
			
			// now for the recipient related data
			if(count($campaignRecord->recipientList) > 0)
			{
				$emailProviderRecipientListIdArr = array();
				foreach($campaignRecord->recipientList as $list)
				{
					$emailProviderRecipientListIdArr[$list->emailProviderRecipientListId] = $list->emailProviderRecipientListId;
				}
				
				$campaignModel->emailProviderRecipientListId = $emailProviderRecipientListIdArr;
			}
			
			return $campaignModel;
		}
	}

	/**
	 * Process the 'save campaign' action.
	 *
	 * @param MasterBlaster_CampaignModel $campaign
	 * @throws \Exception
	 * @return bool
	 */
	public function saveCampaign(MasterBlaster_CampaignModel $campaign)
	{
		// since we have to perform saves on multiple entities, 
		// it's all or nothing using sql transactions
		$transaction = craft()->db->beginTransaction();
		
		try // save the campaign
		{
			$campaignRecord = $this->_saveCampaign($campaign);
			if($campaignRecord->hasErrors()) // no good
			{
				$transaction->rollBack();
				return false;
			}
		}
		catch (\Exception $e)
		{
			throw new Exception(Craft::t('Error: Campaign could not be saved.'));
		}		
				
		try // save & associate the recipient list
		{
			$this->_saveRecipientList($campaign, $campaignRecord); // we're passing the model by reference, so need to return anything
			if($campaign->hasErrors()) // no good
			{				
				$transaction->rollBack();
				return false;
			}
		}
		catch (\Exception $e)
		{
			throw new Exception(Craft::t('Error: Email recipient list could not be saved.'));
		}
		
		$transaction->commit();
		
		$this->_cleanUpRecipientListOrphans($campaignRecord);

		return $campaignRecord->id;
	}
	

	
	/**
	 * Function for saving campaign and template data ONLY (no recipient stuff here)
	 * @param MasterBlaster_CampaignModel $campaign
	 * @throws Exception
	 * @return \Craft\MasterBlaster_CampaignRecord
	 */
	private function _saveCampaign(MasterBlaster_CampaignModel &$campaign)
	{
		if (isset($campaign->id) && $campaign->id)
		{
			$campaignRecord = MasterBlaster_CampaignRecord::model()->findById($campaign->id);
		
			if ( ! $campaignRecord)
			{
				throw new Exception(Craft::t('No campaign exists with the ID “{id}”', array('id' => $campaign->id)));
			}
		
			$oldCampaignName = $campaignRecord->name;
		}
		else
		{
			$campaignRecord = new MasterBlaster_CampaignRecord();
		}
		
		// Set common attributes
		$campaignRecord->name			= $campaign->name;
		$campaignRecord->subject		= $campaign->subject;
		$campaignRecord->fromEmail		= $campaign->fromEmail;
		$campaignRecord->fromName		= $campaign->fromName;
		$campaignRecord->replyToEmail	= $campaign->replyToEmail;
		$campaignRecord->emailProvider	= $campaign->emailProvider;
		$campaignRecord->templateOption	= $campaign->templateOption;
		
		// template specific attributes & validation
		switch($campaign->templateOption)
		{
			case 1: // Import the HTML/Text on your own
				$campaignRecord->htmlBody	= $campaign->htmlBody;
				$campaignRecord->textBody	= $campaign->textBody;
				$campaignRecord->addRules(array('htmlBody,textBody', 'required'));
				break;
			case 2: // Send a simple text-based email
				$campaignRecord->textBody	= $campaign->textBody;
				$campaignRecord->addRules(array('textBody', 'required'));
				break;
			case 3: // Create a Campaign based on an Entries Section and Template
				$campaignRecord->sectionId		= $campaign->sectionId;
				$campaignRecord->htmlTemplate	= $campaign->htmlTemplate;
				$campaignRecord->textTemplate	= $campaign->textTemplate;
				$campaignRecord->addRules(array('sectionId,htmlTemplate,textTemplate', 'required'));
				break;
		}
		
		$campaignRecord->validate();
		$campaign->addErrors($campaignRecord->getErrors());
		
		if( ! $campaignRecord->hasErrors())
		{
			$campaignRecord->save(false);
		}
		
		return $campaignRecord;
	}
	
	/**
	 * @param MasterBlaster_CampaignModel $campaign
	 * @param MasterBlaster_CampaignRecord $campaignRecord
	 * @throws Exception
	 */
	private function _saveRecipientList(MasterBlaster_CampaignModel &$campaign, MasterBlaster_CampaignRecord &$campaignRecord)
	{
		// an email provider is required
		if( ! isset($campaign->emailProvider) || ! $campaign->emailProvider)
		{
			$campaign->addError('emailProvider', 'Unsupported email provider.');
		}
		
		// if a new recipient list is passed in, handle that first
		if($campaign->recipientOption == 2)
		{
			
			$recipientIds = array();
			
			// parse and create individual recipients as needed
			if( ! $recipients = explode("\r\n", $campaign->recipients))
			{
				$campaign->addError('recipients', 'You must add at least one valid email.');
				return false;
			}
			foreach($recipients as $email)
			{
				$recipientRecord = new MasterBlaster_RecipientRecord();
				
				// first let's check if the email already exists
				$recipientRecord->find('email=:email', array(':email' => $email));

				if($recipientRecord->id)
				{
					$recipientIds[] = $recipientRecord->id;
					continue;
				}
				
				$recipientRecord->email = $email;
				$recipientRecord->save(false);
				if($recipientRecord->hasErrors())
				{
					$campaign->addError('recipients', 'Once or more of listed emails are not valid.');
					return false;
				}
				
				$recipientIds[] = $recipientRecord->id;				
			}
			
			// create a new local recipient list
			$localRecipientList = new MasterBlaster_LocalRecipientListRecord();
			$localRecipientList->name = 'Auto-generated ' . date('m.d.Y G:i');
			$localRecipientList->save(false);			
			
			foreach($recipientIds as $recipientId)
			{
				$localRecipientListAssignment = new MasterBlaster_LocalRecipientListAssignmentRecord();
				$localRecipientListAssignment->localRecipientListId = $localRecipientList->id;
				$localRecipientListAssignment->recipientId = $recipientId;
				$localRecipientListAssignment->save(false);
			}
			
			$campaign->emailProviderRecipientListId = $localRecipientList->id;
			
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
			
			$recipeintListIds = (array) $campaign->emailProviderRecipientListId;
	
			// process each recipient listCampaigns
			foreach($recipeintListIds as $list_id)
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
				if( ! in_array($list->emailProviderRecipientListId, $recipeintListIds))
				{
					craft()->masterBlaster->deleteCampaignRecipientList($list->id, $campaignRecord->id);
				}
			}
		}
	}
	
	private function _cleanUpRecipientListOrphans(&$campaignRecord)
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
	
	/**
	 * Delete recipient list
	 * @param int $recipientListId
	 * @return bool
	 */
	public function deleteRecipientList($recipientListId)
	{
		return craft()->db->createCommand()->delete('masterblaster_recipient_lists', array('id' => $recipientListId));
	}
	
	/**
	 * Delete campaign recipient list entry
	 * @param int $recipientListId
	 * @param int $campaignId
	 * @return bool
	 */
	public function deleteCampaignRecipientList($recipientListId, $campaignId)
	{
		return craft()->db->createCommand()->delete('masterblaster_campaign_recipient_lists', 
				array('recipientListId' => $recipientListId, 'campaignId' => $campaignId));
	}

	/**
	 * Deletes a campaign by its ID along with associations;
	 * also cleans up any remaining orphans
	 *
	 * @param int $campaignId
	 * @return bool
	*/
	public function deleteCampaign($campaignId)
	{
		// since we have to perform deletes on multiple entities,
		// it's all or nothing using sql transactions
		$transaction = craft()->db->beginTransaction();
		
		try
		{
			// get associated recipient lists so we can use them for cleaning up later
			$criteria = new \CDbCriteria();
			$criteria->condition = 'campaignId=:campaignId';
			$criteria->params = array(':campaignId' => $campaignId);
			$campaignRecord = MasterBlaster_CampaignRecord::model()
			->with('recipientList')
			->find($criteria);			
			
			// delete campaign
			if( ! craft()->db->createCommand()->delete('masterblaster_campaigns', array('id' => $campaignId)))
			{
				$transaction->rollback();
				return false;
			}
			
			// delete associated recipient lists
			if( ! craft()->db->createCommand()->delete('masterblaster_campaign_recipient_lists', array('campaignId' => $campaignId)))
			{
				$transaction->rollback();
				return false;
			}
		
			if( ! $this->_cleanUpRecipientListOrphans($campaignRecord))
			{
				$transaction->rollback();
				return false;
			}
			
		}
		catch (\Exception $e)
		{
			$transaction->rollback();
			return false;
		}

		$transaction->commit();
		return true;
	}
	
	public function getTemplatesDirListing()
	{
		$templates_path = craft()->path->getSiteTemplatesPath();
		$files = $this->_scan($templates_path);
		$select_options = array();
		 
		// set keys the same as values for <select> element
		foreach($files as $file)
		{
			$fileArr = explode('.', $file);
			array_pop($fileArr);
			$select_options[$file] = implode('.', $fileArr);
		}
		return $select_options;
	}
	
	public function getNotifications()
	{
		return MasterBlaster_CampaignRecord::model()->getNotifications();
	}
	
	public function getNotificationEvents()
	{
		$events = MasterBlaster_NotificationEventRecord::model()->findAll();
		$events_list = array();
		foreach($events as $event)
		{
			$events_list[$event->id] = $event->description;
		}
		return $events_list;
	}
	

	private function _scan($dir, $prefix = '')
	{
		$dir = rtrim($dir, '\\/');
		$result = array();
	
		foreach (scandir($dir) as $f)
		{
			if ($f !== '.' and $f !== '..')
			{
				if (is_dir("{$dir}/{$f}"))
				{
					$result = array_merge($result, $this->_scan("{$dir}/{$f}", "{$prefix}{$f}/"));
				}
				else
				{
					$result[] = $prefix.$f;
				}
			}
		}
	
		return $result;
	}
}
