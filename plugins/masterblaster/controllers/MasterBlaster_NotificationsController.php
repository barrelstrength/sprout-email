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
	 * @return void
	 */
	public function actionSave()
	{
		// first save the campaign as normally would be done
		if($campaignId = parent::actionSave())
		{
			// since this is a notification, we'll make an event/campaign association
			craft()->masterBlaster_notifications->associateCampaign($campaignId, craft()->request->getPost('notificationEvent'));
		}
	}
}