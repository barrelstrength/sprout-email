<?php
namespace Craft;

class MasterBlaster_CampaignNotificationEventRecord extends BaseRecord
{	
	/**
	 * Return table name corresponding to this record
	 * 
	 * @return string
	 */
	public function getTableName()
	{
		return 'masterblaster_campaign_notification_events';
	}

	/**
	 * These have to be explicitly defined in order for the plugin to install
	 * 
	 * @return array
	 */
    public function defineAttributes()
    {
        return array(
        	'notificationEventId' => array(AttributeType::Number),
        	'campaignId' => array(AttributeType::Number),    
        	'options' => array(AttributeType::String, 'column' => ColumnType::Text),   	
			'dateCreated' => array(AttributeType::DateTime),
        	'dateUpdated' => array(AttributeType::DateTime),
        );
    }
    
    /**
     * Associates a notification campaign with an event
     * 
     * @param int $campaignId
     * @param int $notificationEventId
     */
    public function associateCampaignEvent($campaignId, $notificationEventId)
    {
    	// try to get it, if exists, update else create new
    	$criteria = new \CDbCriteria();
    	$criteria->condition = 'campaignId=:campaignId';
    	$criteria->params = array(':campaignId' => $campaignId);
    	if( ! $campaignNotification = MasterBlaster_CampaignNotificationEventRecord::model()->find($criteria))
    	{
    		$campaignNotification = new MasterBlaster_CampaignNotificationEventRecord();
    	}
    	
    	$campaignNotification->campaignId = $campaignId;
    	$campaignNotification->notificationEventId = $notificationEventId;
    	return $campaignNotification->save(false);
    }
    
    /**
     * Returns campaigns which meet the event and event options criteria
     * 
     * @param string $event
     * @param obj $entry
     * @return array of MasterBlaster_CampaignRecord objects
     */
    public function getCampaignEventNotifications($event, $entry)
    {    	
    	$criteria = new \CDbCriteria();
    	$criteria->condition = 'event=:event';
    	$criteria->params = array(':event' => $event);
    	
    	$res =  MasterBlaster_NotificationEventRecord::model()
    	->with('campaign','campaign.recipientList','campaignNotificationEvent')
    	->find($criteria);

    	if( ! isset($res->campaign) || ! $res->campaign)
    	{
    		return false;
    	}
    	
  		$notificationCampaignIds = array();
  		
  		// these match the event; now we need to narrow down by event options
    	foreach($res->campaignNotificationEvent as $key => $campaignNotification)
    	{
    		$notificationCampaignIds[$key] = $campaignNotification->campaignId; // assume it's a match
    		if($opts = unserialize($campaignNotification->options)) // get options, if any
    		{    			
    			foreach($opts['options'] as $option_key => $option) // process each option set associated with the campagin
    			{
    				if( ! is_array($option))
    				{
    					continue;
    				}

	    			switch($option_key)
				    {
				    	case 'userGroupIds':
				    		
				    		// process 'on user save' type events
				    		if($entry->elementType == 'User')
				    		{				    			
				    			$groups_arr = array();				    			
				    			if($groups = craft()->userGroups->getGroupsByUserId($entry->id))
				    			{
					    			foreach($groups as $group)
					    			{
					    				if( ! in_array($group->id, $option))
					    				{
					    					unset($notificationCampaignIds[$key]);
					    					continue;
					    				}
					    			}
				    			}
				    			else 
				    			{
				    				unset($notificationCampaignIds[$key]);
				    				continue;
				    			}
				    		}
				    		
				    		// process 'on content save' type events
				    		else if(strpos(get_class($entry), 'EntryModel') !== false)
				    		{
				    		
				    			if( ! in_array($entry->sectionId, $option))
				    			{
				    				unset($notificationCampaignIds[$key]);
				    				continue;
				    			}
				    		}
				    		break;
				    	case 'sectionIds':

				    		// process 'on content save' type events
				    		if(strpos(get_class($entry), 'EntryModel') !== false)
				    		{
				    			if( ! in_array($entry->sectionId, $option))
				    			{
				    				unset($notificationCampaignIds[$key]);
				    				continue;
				    			}
				    		}
				    		break;
				    }
    			}
    		}
    	}

    	// compile an array of campaign objects and return
    	$notificationCampaigns = array();
    	foreach($res->campaign as $campaign)
    	{
    		if(in_array($campaign->id, $notificationCampaignIds))
    		{
    			$notificationCampaigns[] = $campaign;
    		}
    	}
    	
    	return $notificationCampaigns;
    }
    
    public function setCampaignNotificationEventOptions($campaignId, $data)
    {
    	// handle options
    	$options = array();
    	switch($data['notificationEvent'])
    	{
    		case 1: // entries.saveEntry
    			$options = array('options' => array(
						'sectionIds' => $data['entriesSaveEntryNewSectionIds'],
						'userGroupIds' => $data['entriesSaveEntryNewUseroptIds']
				));
    			break;
    		case 4: // users.saveProfile
    			$options = array('options' => array(
    					
						'userGroupIds' => $data['usersSaveProfileUseroptIds']
				));
    			break;
    		case 3: // users.saveUser
    			$options = array('options' => array(
						'userGroupIds' => $data['usersSaveUserUseroptIds']
				));
    			break;
    		case 2: // content.saveContent
    			$options = array('options' => array(
						'sectionIds' => $data['entriesSaveEntrySectionIds'],
						'userGroupIds' => $data['entriesSaveEntryUseroptIds']
				));
    			break;
    		default:
    			$options = array('options' => $data['options'] ? $data['options'] : array());
    			break;
    	}

    	$criteria = new \CDbCriteria();
    	$criteria->condition = 'campaignId=:campaignId';
    	$criteria->params = array(':campaignId' => $campaignId);
    	if( ! $campaignNotification = MasterBlaster_CampaignNotificationEventRecord::model()->find($criteria))
    	{
    		return false;
    	}
    	
    	$campaignNotification->options = serialize($options);
    	return $campaignNotification->save(false);
    }
}
