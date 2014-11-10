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
		return 'sproutemail_notificationevents';
	}
	
	/**
	 * These have to be explicitly defined in order for the plugin to install
	 *
	 * @return array
	 */
	public function defineAttributes()
	{
		return array (
			'registrar'   => AttributeType::String,
			'event'       => AttributeType::String,
			'description' => AttributeType::String,
			'options'     => AttributeType::Mixed,
			'dateCreated' => AttributeType::DateTime,
			'dateUpdated' => AttributeType::DateTime 
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
			'emailBlastTypeNotificationEvent' => array (
				self::HAS_MANY,
				'SproutEmail_EmailBlastTypeNotificationEventRecord',
				'notificationEventId' 
			),
			'emailBlastType' => array (
				self::HAS_MANY,
				'SproutEmail_EmailBlastTypeRecord',
				'emailBlastTypeId',
				'through' => 'emailBlastTypeNotificationEvent' 
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
		
		return SproutEmail_NotificationEventRecord::model()->with( 'emailBlastType', 'emailBlastType.recipientList' )->findAll( $criteria );
	}
}
