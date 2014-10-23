<?php
namespace Craft;

/**
 * Notifications service
 */
class SproutEmail_NotificationsService extends BaseApplicationComponent
{
	/**
	 * Associates a notifcation emailBlastType with an event
	 *
	 * @param int $emailBlastTypeId            
	 * @param int $notificationEventId            
	 */
	public function associateEmailBlastType($emailBlastTypeId, $notificationEventId)
	{
		return SproutEmail_EmailBlastTypeNotificationEventRecord::model()->associateEmailBlastTypeEvent( $emailBlastTypeId, $notificationEventId );
	}
	
	/**
	 * Sets notification event options
	 *
	 * @param int $emailBlastTypeId            
	 * @param array $data
	 *            ($_POST)
	 */
	public function setEmailBlastTypeNotificationEventOptions($emailBlastTypeId, $data)
	{
		return SproutEmail_EmailBlastTypeNotificationEventRecord::model()->setEmailBlastTypeNotificationEventOptions( $emailBlastTypeId, $data );
	}
	
	/**
	 * Returns all emailBlastType notifications based on the passed event
	 *
	 * @param string $event            
	 * @param object $entry            
	 */
	public function getEventNotifications($event, $entry)
	{
		return SproutEmail_EmailBlastTypeNotificationEventRecord::model()->getEmailBlastTypeEventNotifications( $event, $entry );
	}
	
	/**
	 * Return emailBlastType notification given emailBlastType id
	 * 
	 * @param int $emailBlastTypeId
	 */
	public function getEmailBlastTypeNotificationByEmailBlastTypeId($emailBlastTypeId)
	{
		$criteria = new \CDbCriteria();
		$criteria->condition = 'emailBlastTypeId=:emailBlastTypeId';
		$criteria->params = array (
				':emailBlastTypeId' => $emailBlastTypeId 
		);
		
		return SproutEmail_EmailBlastTypeNotificationEventRecord::model()->with('notificationEvent')->find( $criteria );
	}
}
