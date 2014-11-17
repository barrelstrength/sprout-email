<?php
namespace Craft;

/**
 * Notifications service
 */
class SproutEmail_NotificationsService extends BaseApplicationComponent
{	
	/**
	 * Returns all supported notification events
	 *
	 * @return array
	 */
	public function getNotificationEvents($event = null)
	{
		if ( $event )
		{
			$criteria = new \CDbCriteria();
			$criteria->condition = 'event!=:event';
			$criteria->params = array (
					':event' => 'craft' 
			);
		}
		$events = SproutEmail_NotificationEventRecord::model()->findAll();
		$events_list = array ();
		foreach ( $events as $event )
		{
			$events_list [$event->id] = $event;
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
		return SproutEmail_NotificationEventRecord::model()->findByPk( $id );
	}
	
	/**
	 * Returns event option file names
	 *
	 * @return array
	 */
	public function getNotificationEventOptions()
	{
		$options = craft()->sproutEmail->scan( dirname( __FILE__ ) . '/../templates/settings/notifications/_options' );
		
		$criteria = new \CDbCriteria();
		$criteria->condition = 'registrar!=:registrar';
		$criteria->params = array (
				':registrar' => 'craft' 
		);
		$events = SproutEmail_NotificationEventRecord::model()->findAll( $criteria );

		foreach ( $events as $event )
		{
			$options ['plugin_options'] [$event->id] = $event->options;
		}
		
		return $options;
	}
	
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
