<?php
namespace Craft;

/**
 * Main MasterBlaster service
 */
class MasterBlaster_NotificationsService extends BaseApplicationComponent
{
	public function associateCampaign($campaignId, $notificationEventId)
	{
		return MasterBlaster_CampaignNotificationEventRecord::associateCampaignEvent($campaignId, $notificationEventId); 
	}
	
	public function getEventNotifications($event)
	{
		$criteria = new \CDbCriteria();
		$criteria->condition = 'event=:event';
		$criteria->params = array(':event' => $event);
		
		return MasterBlaster_NotificationEventRecord::model()->with('campaign','campaign.recipientList')->findAll($criteria);
	}
}
