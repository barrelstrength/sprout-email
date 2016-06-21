<?php
namespace Craft;

/**
 * Class SproutEmail_NotificationController
 *
 * @package Craft
 */
class SproutEmail_NotificationController extends BaseController
{

	public function actionEditNotification(array $variables = array())
	{
		$notificationId = craft()->request->getSegment(4);

		if (!isset($variables['notification']))
		{
			if (is_numeric($notificationId))
			{

			}
			else
			{
				$variables['notification'] = new SproutEmail_NotificationModel();
			}
		}

		$this->renderTemplate('sproutemail/notifications/_settings', $variables);
	}

}
