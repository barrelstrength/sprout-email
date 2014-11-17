<?php
namespace Craft;

/**
 * Notifications controller
 */
class SproutEmail_NotificationsController extends SproutEmail_CampaignController
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
		$campaignId = array_shift( $parts );
		
		// get the notification
		$campaignNotification = craft()->sproutEmail_notifications->getCampaignNotificationByCampaignId( $id );
		
		// authenticate
		if( ! $campaignNotification->options || ! isset($campaignNotification->options['options']['cronHash']))
		{
			die('Invalid Request');
		}

		if ( $campaignNotification->options['options']['cronHash'] != $id || $campaignNotification->notificationEvent->event != 'cron' )
		{
			die( 'Invalid request' );
		}
		
		// get campaign and send
		$campaign = craft()->sproutEmail_campaign->getCampaignById($id);
		$service = 'sproutEmail_' . lcfirst( $campaign->emailProvider );
		craft()->{$service}->sendEntry( $campaign );
		
		exit( 0 );
	}


	public function actionNotificationSettingsTemplate(array $variables = array())
	{
		if (isset($variables['campaignId'])) 
		{
			// If campaign already exists, we're returning an error object
			if ( ! isset($variables['campaign']) ) 
			{
				$variables['campaign'] = craft()->sproutEmail_campaign->getCampaignById($variables['campaignId']);
			}
		}
		else
		{	
			$variables['campaign'] = new SproutEmail_Campaign();
		}
		
		// Load our template
		$this->renderTemplate('sproutemail/settings/notifications/_edit', $variables);
	}
}