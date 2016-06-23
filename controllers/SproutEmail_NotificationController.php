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

		$newNotification = craft()->httpSession->get('newNotification');

		if (isset($variables['notification']))
		{
			$notification = $variables['notification'];
		}
		elseif ($newNotification != null)
		{
			$notification = unserialize($newNotification);
		}
		else
		{
			$notification = new SproutEmail_NotificationEmailModel();
		}

		$this->renderTemplate('sproutemail/notifications/_settings', array(
			'notification'    => $notification,
			'newNotification' => $newNotification,
			'redirectEnd'     => $redirectEnd
		));
	}

	public function actionSaveNotificationSetting()
	{
		$this->requirePostRequest();

		$notificationId = craft()->request->getRequiredPost('sproutEmail.id');

		if ($notificationId != null)
		{
			$notification = craft()->elements->getElementById($notificationId);
		}
		else
		{
			$notification = new SproutEmail_NotificationEmailModel();

			$inputs = craft()->request->getPost('sproutEmail');
			$inputs['subjectLine']	= $inputs['name'];
			$inputs['slug']         = ElementHelper::createSlug($inputs['name']);
		}

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

			craft()->httpSession->add('newNotification', serialize($notification));

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
		$notificationId = null;
		$redirectEnd    = "new";

		if (isset($variables['notificationId']))
		{
			$notificationId = $variables['notificationId'];
			$redirectEnd    = $notificationId;
		}

		$newNotification = craft()->httpSession->get('newNotification');

		if ($newNotification == null && !isset($variables['notificationId']))
		{
			$url = UrlHelper::getCpUrl() . '/sproutemail/notifications/new';
			$this->redirect($url);
		}

		if ($newNotification != null)
		{
			$notification = unserialize($newNotification);
		}

		$mailer = sproutEmail()->mailers->getMailerByName('defaultmailer');
		$recipientLists = sproutEmail()->entries->getRecipientListsByEntryId($notificationId);

		$notificationEvent = null;

		$this->renderTemplate('sproutemail/notifications/_edit',  array(
			'notification'      => $notification,
			'notificationEvent' => $notificationEvent,
			'redirectEnd'       => $redirectEnd,
			'recipientLists'    => $recipientLists,
			'mailer'            => $mailer,
			'showPreviewBtn'    => false
		));
	}

	public function actionSaveNotification()
	{
		$this->requirePostRequest();

		$notificationId = craft()->request->getRequiredPost('sproutEmail.id');

		$newNotification = craft()->httpSession->get('newNotification');

	}
}
