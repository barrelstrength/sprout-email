<?php
namespace Craft;

/**
 * Class SproutEmail_NotificationController
 *
 * @package Craft
 */
class SproutEmail_NotificationController extends BaseController
{

	public function actionEditNotificationSetting(array $variables = array())
	{
		$notificationId = null;
		// Set redirect ending url after saving setting
		$redirectEnd    = "new";

		if (isset($variables['notificationId']))
		{
			$notificationId = $variables['notificationId'];
			$redirectEnd    = $notificationId;
		}

		if (isset($variables['notification']))
		{
			$notification = $variables['notification'];
		}
		else
		{
			if (is_numeric($notificationId))
			{

			}
			else
			{
				$notification = new SproutEmail_NotificationEmailModel();
			}
		}

		$this->renderTemplate('sproutemail/notifications/_settings', array(
			'notificationId' => $notificationId,
			'notification'   => $notification,
			'redirectEnd'    => $redirectEnd
		));
	}

	public function actionSaveNotificationSetting()
	{
		$this->requirePostRequest();

		$notificationId = craft()->request->getRequiredPost('sproutEmail.id');

		$notification = new SproutEmail_NotificationEmailModel();

		if ($notificationId != null)
		{
			$notification = craft()->elements->getElementById($notificationId);
		}

		$inputs = craft()->request->getPost('sproutEmail');

		$notification->setAttributes($inputs);

		if($notification->validate())
		{

			if (craft()->request->getPost('fieldLayout'))
			{
				// Set the field layout
				$fieldLayout = craft()->fields->assembleLayoutFromPost();

				$fieldLayout->type = 'SproutEmail_Notification';

				$notification->setFieldLayout($fieldLayout);
			}

			$fieldLayout = $notification->getFieldLayout();

			$notificationSetting = array(
				'model'       => $notification,
				'fieldLayout' => $fieldLayout
			);

			craft()->httpSession->add('newNotificationSetting', serialize($notificationSetting));

			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Unable to save campaign.'));

			craft()->urlManager->setRouteVariables(array(
				'notification' => $notification
			));
		}
	}

	public function actionEditNotification(array $variables = array())
	{
		$notificationSetting = craft()->httpSession->get('newNotificationSetting');

		if ($notificationSetting != null)
		{
			$notificationSetting = unserialize($notificationSetting);
		}

		$this->renderTemplate('sproutemail/notifications/_edit', array());
	}
}
