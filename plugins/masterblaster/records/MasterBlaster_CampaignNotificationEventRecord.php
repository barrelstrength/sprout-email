<?php
namespace Craft;

class MasterBlaster_CampaignNotificationEventRecord extends BaseRecord
{	
	/**
	 * Return table name corresponding to this record
	 * @return string
	 */
	public function getTableName()
	{
		return 'masterblaster_campaign_notification_events';
	}

	/**
	 * These have to be explicitly defined in order for the plugin to install
	 * @return array
	 */
    public function defineAttributes()
    {
        return array(
        	'notificationEventId' => array(AttributeType::Number),
        	'campaignId' => array(AttributeType::Number),        	
			'dateCreated' => array(AttributeType::DateTime),
        	'dateUpdated' => array(AttributeType::DateTime),
        );
    }
    
    public static function associateCampaignEvent($campaignId, $notificationEventId)
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
}
