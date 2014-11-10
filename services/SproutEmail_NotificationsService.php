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
		$options = craft()->sproutEmail->scan( dirname( __FILE__ ) . '/../templates/notifications/_options' );
		
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
