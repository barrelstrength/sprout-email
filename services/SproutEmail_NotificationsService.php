<?php
namespace Craft;

/**
 * Notifications service
 */
class SproutEmail_NotificationsService extends BaseApplicationComponent
{
	/**
	 * Associates a notifcation campaign with an event
	 *
	 * @param int $campaignId            
	 * @param int $notificationEventId            
	 */
	public function associateCampaign($campaignId, $notificationEventId)
	{
		return SproutEmail_CampaignNotificationEventRecord::model()->associateCampaignEvent( $campaignId, $notificationEventId );
	}
	
	/**
	 * Sets notification event options
	 *
	 * @param int $campaignId            
	 * @param array $data
	 *            ($_POST)
	 */
	public function setCampaignNotificationEventOptions($campaignId, $data)
	{
		return SproutEmail_CampaignNotificationEventRecord::model()->setCampaignNotificationEventOptions( $campaignId, $data );
	}
	
	/**
	 * Returns all campaign notifications based on the passed event
	 *
	 * @param string $event            
	 * @param object $entry            
	 */
	public function getEventNotifications($event, $entry)
	{
		return SproutEmail_CampaignNotificationEventRecord::model()->getCampaignEventNotifications( $event, $entry );
	}
	
	/**
	 * Return campaign notification given campaign id
	 * 
	 * @param int $campaignId
	 */
	public function getCampaignNotificationByCampaignId($campaignId)
	{
		$criteria = new \CDbCriteria();
		$criteria->condition = 'campaignId=:campaignId';
		$criteria->params = array (
				':campaignId' => $campaignId 
		);
		
		return SproutEmail_CampaignNotificationEventRecord::model()->with('notificationEvent')->find( $criteria );
	}
}
