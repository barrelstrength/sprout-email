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

		$session = craft()->httpSession->get('newNotification');

		$notification = sproutEmail()->notificationemail->getNotificationByVariables($variables, $session);

		$this->renderTemplate('sproutemail/notifications/_settings', array(
			'notification'    => $notification,
			'newNotification' => $session
		));
	}

	public function actionSaveNotificationSetting()
	{
		$this->requirePostRequest();

		$inputs = craft()->request->getPost('sproutEmail');

		if (isset($inputs['id']))
		{
			$notificationId = $inputs['id'];

			$notification = craft()->elements->getElementById($notificationId);
		}
		else
		{
			$notification = new SproutEmail_NotificationEmailModel();

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

				// Remove previous field layout
				craft()->fields->deleteLayoutById($notification->fieldLayoutId);

				craft()->fields->saveLayout($fieldLayout);
			}

			if (isset($inputs['id']))
			{
				sproutEmail()->notificationemail->saveNotification($notification);
			}
			else
			{
				craft()->httpSession->add('newNotification', serialize($notification));
			}

			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Unable to save setting.'));

			craft()->urlManager->setRouteVariables(array(
				'notification' => $notification
			));
		}
	}

	public function actionEditNotification(array $variables = array())
	{
		$notificationId = null;

		$session = craft()->httpSession->get('newNotification');

		if ($session == null && !isset($variables['notificationId']))
		{
			$url = UrlHelper::getCpUrl() . '/sproutemail/notifications/setting/new';
			$this->redirect($url);
		}

		$notification = sproutEmail()->notificationemail->getNotificationByVariables($variables, $session);

		$mailer = sproutEmail()->mailers->getMailerByName('defaultmailer');
		$recipientLists = sproutEmail()->entries->getRecipientListsByEntryId($notificationId);

		$this->renderTemplate('sproutemail/notifications/_edit',  array(
			'notification'      => $notification,
			'recipientLists'    => $recipientLists,
			'mailer'            => $mailer,
			'showPreviewBtn'    => false
		));
	}

	public function actionSaveNotification()
	{
		$this->requirePostRequest();

		$inputs = craft()->request->getPost('sproutEmail');

		$session = craft()->httpSession->get('newNotification');

		if (isset($inputs['id']))
		{
			$notificationId = $inputs['id'];

			$notification = craft()->elements->getElementById($notificationId);
		}

		if ($session != null)
		{
			$session = unserialize($session);
			$notification = $session;
		}

		$notification->setAttributes($inputs);

		$notification = $this->validateAttribute('fromName', 'From Name', $inputs['fromName'], $notification, false);

		$notification = $this->validateAttribute('fromEmail', 'From Email', $inputs['fromEmail'], $notification);

		$notification = $this->validateAttribute('replyToEmail', 'Reply To', $inputs['replyToEmail'], $notification);

		// Clear errors to additional errors
		if($notification->validate(null, false) && $notification->hasErrors() == false)
		{
			$notification->clearErrors();

			$fieldsLocation = craft()->request->getParam('fieldsLocation', 'fields');

			$notification->setContentFromPost($fieldsLocation);
			$notification->setContentPostLocation($fieldsLocation);

			$notification->getContent()->title = $notification->subjectLine;

			sproutEmail()->notificationemail->saveNotification($notification);

			if (craft()->httpSession->get('newNotification') != null)
			{
				craft()->httpSession->remove('newNotification');
			}

			$this->redirectToPostedUrl();
		}
		else
		{
			$this->returnErrors($notification);
		}
	}

	private function validateAttribute($attribute, $label, $value, $notification, $email = true)
	{
		if (empty($value))
		{
			$notification->addError($attribute, Craft::t("$label cannot be blank."));
		}

		if ($email == true && !filter_var($value, FILTER_VALIDATE_EMAIL))
		{
			$notification->addError($attribute, Craft::t("$label is not a valid email address."));
		}

		return $notification;
	}

	private function returnErrors($notification)
	{
		craft()->userSession->setError(Craft::t('Unable to save notification email.'));

		craft()->urlManager->setRouteVariables(array(
			'notification' => $notification
		));
	}
}
