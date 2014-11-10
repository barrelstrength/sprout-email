<?php
namespace Craft;

/**
 * Notifications controller
 */
class SproutEmail_NotificationsController extends SproutEmail_EmailBlastTypeController
{
	/**
	 * Save emailBlastType
	 *
	 * @return void
	 */
	public function actionSaveNotification()
	{
		// first save the emailBlastType as normally would be done
		$emailBlastTypeModel = parent::actionSaveEmailBlastType();
		
		if ($emailBlastTypeModel->getErrors())
		{
			// Send the field back to the template
			craft()->urlManager->setRouteVariables(array(
				'emailBlastType' => $emailBlastTypeModel 
			));
		}
		else
		{
			// convert notificationEvent to id
			if ( isset( $_POST ['notificationEvent'] ) && ! is_numeric( $_POST ['notificationEvent'] ) )
			{
				$criteria = new \CDbCriteria();
				$criteria->condition = 'event=:event';
				$criteria->params = array (
						':event' => str_replace( '---', '.', $_POST ['notificationEvent'] ) 
				);
				
				if ( $res = SproutEmail_NotificationEventRecord::model()->find( $criteria ) )
				{
					$_POST ['notificationEvent'] = ( int ) $res->id;
				}
			}
			
			if ( isset( $_POST ['notificationEvent'] ) )
			{
				// since this is a notification, we'll make an event/emailblasts association...
				craft()->sproutEmail_notifications->associateEmailBlastType( $emailBlastTypeModel->id, $_POST ['notificationEvent'] );
				
				// ... and set notification options
				craft()->sproutEmail_notifications->setEmailBlastTypeNotificationEventOptions( $emailBlastTypeModel->id, $_POST );
			}
			
			craft()->userSession->setNotice( Craft::t( 'Notification successfully saved.' ) );
			
			switch (craft()->request->getPost( 'continue' ))
			{
				case 'info' :
					$this->redirect( 'sproutemail/notifications/edit/' . $emailBlastTypeModel->id . '/recipients' );
					break;

				case 'recipients' :
					$this->redirect( 'sproutemail/notifications/edit/' . $emailBlastTypeModel->id . '/template' );
					break;

				default :
					$this->redirectToPostedUrl($emailBlastTypeModel);
					break;
			}
		}
	}
	
	/**
	 * Trigger notification
	 */
	public function actionTrigger()
	{
		if ( ! $id = craft()->request->getSegment( 5 ) )
		{
			die( 'Invalid request' );
		}
		
		$parts = explode( '-', $id );
		$emailBlastTypeId = array_shift( $parts );
		
		// get the notification
		$emailBlastTypeNotification = craft()->sproutEmail_notifications->getEmailBlastTypeNotificationByEmailBlastTypeId( $id );
		
		// authenticate
		if( ! $emailBlastTypeNotification->options || ! isset($emailBlastTypeNotification->options['options']['cronHash']))
		{
			die('Invalid Request');
		}

		if ( $emailBlastTypeNotification->options['options']['cronHash'] != $id || $emailBlastTypeNotification->notificationEvent->event != 'cron' )
		{
			die( 'Invalid request' );
		}
		
		// get emailBlastType and send
		$emailBlastType = craft()->sproutEmail_emailBlastType->getEmailBlastType( array (
				'id' => $id 
		) );
		$service = 'sproutEmail_' . lcfirst( $emailBlastType->emailProvider );
		craft()->{$service}->sendEmailBlast( $emailBlastType );
		
		exit( 0 );
	}
}