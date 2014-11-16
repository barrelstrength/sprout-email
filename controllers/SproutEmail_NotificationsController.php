<?php
namespace Craft;

/**
 * Notifications controller
 */
class SproutEmail_NotificationsController extends SproutEmail_EmailBlastTypeController
{	
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
		$emailBlastType = craft()->sproutEmail_emailBlastType->getEmailBlastTypeById($id);
		$service = 'sproutEmail_' . lcfirst( $emailBlastType->emailProvider );
		craft()->{$service}->sendEmailBlast( $emailBlastType );
		
		exit( 0 );
	}


	public function actionNotificationSettingsTemplate(array $variables = array())
	{
		if (isset($variables['emailBlastTypeId'])) 
		{
			// If emailBlastType already exists, we're returning an error object
			if ( ! isset($variables['emailBlastType']) ) 
			{
				$variables['emailBlastType'] = craft()->sproutEmail_emailBlastType->getEmailBlastTypeById($variables['emailBlastTypeId']);
			}
		}
		else
		{	
			$variables['emailBlastType'] = new SproutEmail_EmailBlastType();
		}
		
		// Load our template
		$this->renderTemplate('sproutemail/settings/notifications/_edit', $variables);
	}
}