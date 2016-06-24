<?php
namespace Craft;

/**
 * The NotificationEmail API service layer
 *
 * Class SproutEmail_NotificationsService
 *
 * @package Craft
 */
class SproutEmail_NotificationEmailService extends BaseApplicationComponent
{

	public function getNotificationByVariables($variables, $session = null, $elementService = null)
	{
		$notification = null;

		if (isset($variables['notification']))
		{
			$notification = $variables['notification'];
		}
		elseif (isset($variables['notificationId']))
		{
			$notificationId = $variables['notificationId'];

			$notification = $elementService->getElementById($notificationId);
		}
		elseif ($session != null)
		{
			$notification = unserialize($session);
		}
		else
		{
			$notification = new SproutEmail_NotificationEmailModel();
		}

		return $notification;
	}
}
