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
	 * Returns campaignRecipient lists
	 * 
	 * @param int $campaignId
	 * @return array
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
	 * @param array possible conditions: array('id' => <id>, 'handle' => <handle>, ...) 
	 * as defined in $valid_keys
	 * @return MasterBlaster_CampaignModel|null
	 */
	public function getCampaign($conditions = array())
	{	
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
			
			$unserialized = array();
			foreach($campaignRecord->campaignNotificationEvent as $event)
			{
				$opts = unserialize($event->options);
				$event->options = isset($opts['options']) ? $opts['options'] : array();
				$unserialized[] = $event;
			}
			
			$campaignModel->notificationEvents = $unserialized;
			

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
	 * Save event
	 * @param MasterBlaster_NotificationEventModel $event
	 * @throws Exception
	 * @return MasterBlaster_NotificationEventModel|\Craft\MasterBlaster_NotificationEventRecord
	 */
	public function saveEvent(MasterBlaster_NotificationEventModel &$event)
	{
		if (isset($event->id) && $event->id)
		{
			$eventRecord = MasterBlaster_NotificationEventRecord::model()->findById($event->id);
		
			if ( ! $eventRecord)
			{
				throw new Exception(Craft::t('No event exists with the ID “{id}”', array('id' => $event->id)));
			}
		}
		else
		{
			$eventRecord = new MasterBlaster_NotificationEventRecord();
		}

		$eventRecord->registrar = $event->registrar;
		$eventRecord->event = $event->event;
		$eventRecord->description = $event->description;
		
		$eventRecord->validate();
		$event->addErrors($eventRecord->getErrors());
		
		if( ! $eventRecord->hasErrors())
		{
			try 
			{
				craft()->plugins->call($event->registrar,array($event->event, function($event, BaseModel $entity, $success = TRUE){}));
			}
			catch (\Exception $e)
			{
				$event->addError('event', $e->getMessage());
				return $event;
			}
			
			$eventRecord->save(false);
		}
		
		return $eventRecord;
	}

	/**
	 * Process the 'save campaign' action.
	 *
	 * @param MasterBlaster_CampaignModel $campaign
	 * @throws \Exception
	 * @return int CampaignRecordId
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
				
		// save & associate the recipient list
		$service = 'masterBlaster_' . $campaignRecord->emailProvider;
		if( ! craft()->{$service}->saveRecipientList($campaign, $campaignRecord))
		{
			$transaction->rollback();
			return false;
		}
		
		$transaction->commit();

		return $campaignRecord->id;
	}
	
	/**
	 * Function for saving campaign and template data ONLY (no recipient stuff here)
	 * 
	 * @param MasterBlaster_CampaignModel $campaign
	 * @throws Exception
	 * @return MasterBlaster_CampaignRecord
	 */
	private function _saveCampaign(MasterBlaster_CampaignModel &$campaign)
	{
		if (isset($campaign->id) && $campaign->id) // this will be an edit
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
	 * Delete campaign recipient list entry
	 * 
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
			$campaignRecord = MasterBlaster_CampaignRecord::model()->findByPk($campaignId);			

			// delete campaign
			if( ! craft()->db->createCommand()->delete('masterblaster_campaigns', array('id' => $campaignId)))
			{
				$transaction->rollback();
				return false;
			}
			
			// delete associated recipients
			$service = 'masterBlaster_' . $campaignRecord->emailProvider;
			if( ! craft()->{$service}->deleteRecipients($campaignRecord))
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
	
	/**
	 * Delete notification event
	 * @param int $id
	 * @return boolean
	 */
	public function deleteEvent($id)
	{
		if( ! craft()->db->createCommand()->delete('masterblaster_notification_events', array('id' => $id)))
		{
			$transaction->rollback();
			return false;
		}
		return true;
	}
	
	/**
	 * Returns all available system frontend templates
	 * 
	 * @return array
	 */
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
	
	/**
	 * Returns all campaign notifications
	 * 
	 * @return array
	 */
	public function getNotifications()
	{
		return MasterBlaster_CampaignRecord::model()->getNotifications();
	}
	
	/**
	 * Returns all supported notification events
	 * 
	 * @return array
	 */
	public function getNotificationEvents($event = null)
	{
		if($event)
		{
			$criteria = new \CDbCriteria();
			$criteria->condition = 'event!=:event';
			$criteria->params = array(':event' => 'craft');
		}
		$events = MasterBlaster_NotificationEventRecord::model()->findAll();
		$events_list = array();
		foreach($events as $event)
		{
			$events_list[$event->id] = $event;
		}
		return $events_list;
	}
	
	/**
	 * Returns single notification event
	 *
	 * @return array
	 */
	public function getNotificationEventById($id = null)
	{
		return MasterBlaster_NotificationEventRecord::model()->findByPk($id);
	}
	
	/**
	 * Returns event option file names
	 * 
	 * @return array
	 */
	public function getNotificationEventOptions()
	{
		$options = $this->_scan(dirname(__FILE__) . '/../templates/notifications/_event_options');
		
		$criteria = new \CDbCriteria();
		$criteria->condition = 'registrar!=:registrar';
		$criteria->params = array(':registrar' => 'craft');
		$events = MasterBlaster_NotificationEventRecord::model()->findAll($criteria);
		foreach($events as $event)
		{
			$options['plugin_options'][$event->id] = $event->options;;
		}
		
		return $options;
	}
	
	/**
	 * Recursive directory scan
	 * 
	 * @param string $dir
	 * @param sring $prefix
	 * @return array
	 */
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
