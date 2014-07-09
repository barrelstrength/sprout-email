<?php
namespace Craft;

/**
 * Notification event record
 */
class SproutEmail_NotificationEventRecord extends BaseRecord
{
	/**
	 * Return table name corresponding to this record
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'sproutemail_notification_events';
	}
	
	/**
	 * These have to be explicitly defined in order for the plugin to install
	 *
	 * @return array
	 */
	public function defineAttributes()
	{
		return array (
			'registrar' => array (
				AttributeType::String 
			),
			'event' => array (
				AttributeType::String 
			),
			'description' => array (
				AttributeType::String 
			),
			'options' => array (
				AttributeType::Mixed 
			),
			'dateCreated' => array (
				AttributeType::DateTime 
			),
			'dateUpdated' => array (
				AttributeType::DateTime 
			) 
		);
	}
	
	/**
	 * Record relationships
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		return array (
			'campaignNotificationEvent' => array (
				self::HAS_MANY,
				'SproutEmail_CampaignNotificationEventRecord',
				'notificationEventId' 
			),
			'campaign' => array (
				self::HAS_MANY,
				'SproutEmail_CampaignRecord',
				'campaignId',
				'through' => 'campaignNotificationEvent' 
			) 
		);
	}
	
	/**
	 * Return event notifications
	 *
	 * @param string $event            
	 */
	public function getEventNotifications($event = null)
	{
		$criteria = new \CDbCriteria();
		
		if ( $event )
		{
			$criteria->condition = 'event=:event';
			$criteria->params = array (
				':event' => $event 
			);
		}
		
		return SproutEmail_NotificationEventRecord::model()->with( 'campaign', 'campaign.recipientList' )->findAll( $criteria );
	}
}
