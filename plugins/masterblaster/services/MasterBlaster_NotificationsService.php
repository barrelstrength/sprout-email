<?php
namespace Craft;

/**
 * Notifications service
 */
class MasterBlaster_NotificationsService extends BaseApplicationComponent
{
	/**
	 * Associates a notifcation campaign with an event
	 * 
	 * @param int $campaignId
	 * @param int $notificationEventId
	 */
	public function associateCampaign($campaignId, $notificationEventId)
	{
		return MasterBlaster_CampaignNotificationEventRecord::model()->associateCampaignEvent($campaignId, $notificationEventId); 
	}
	
	/**
	 * Sets notification event options
	 * 
	 * @param int $campaignId
	 * @param array $data ($_POST)
	 */
	public function setCampaignNotificationEventOptions($campaignId, $data)
	{
		return MasterBlaster_CampaignNotificationEventRecord::model()->setCampaignNotificationEventOptions($campaignId, $data); 
	}
	
	/**
	 * Returns all campaign notifications based on the passed event
	 * 
	 * @param string $event
	 * @param object $entry
	 */
	public function getEventNotifications($event, $entry)
	{
		return MasterBlaster_CampaignNotificationEventRecord::model()->getCampaignEventNotifications($event, $entry);
	}
}
