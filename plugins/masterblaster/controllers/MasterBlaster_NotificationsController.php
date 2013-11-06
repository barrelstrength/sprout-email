<?php
namespace Craft;

/**
 * Notifications controller
 *
 */
class MasterBlaster_NotificationsController extends MasterBlaster_CampaignsController
{
	/**
	 * Save campaign
	 * 
	 * @return void
	 */
	public function actionSave()
	{		
		// first save the campaign as normally would be done
		if($campaignId = parent::actionSave())
		{
			// convert notificationEvent to id
			if(isset($_POST['notificationEvent']) && ! is_numeric($_POST['notificationEvent']))
			{
				$criteria = new \CDbCriteria();
				$criteria->condition = 'event=:event';
				$criteria->params = array(':event' => str_replace('---', '.', $_POST['notificationEvent']));
				 
				if($res =  MasterBlaster_NotificationEventRecord::model()->find($criteria))
				{
					$_POST['notificationEvent'] = (int) $res->id;
				}
			}

			// since this is a notification, we'll make an event/campaign association...
			craft()->masterBlaster_notifications->associateCampaign($campaignId, $_POST['notificationEvent']);
		
			// ... and set notification options
			craft()->masterBlaster_notifications->setCampaignNotificationEventOptions($campaignId, $_POST);
		}
	}
}