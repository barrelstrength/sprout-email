<?php
namespace Craft;

/**
 * Class SproutEmail_NotificationController
 *
 * @package Craft
 */
class SproutEmail_NotificationController extends BaseController
{

	private $notification;

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

			if (!empty($notification->id))
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
		$recipientLists = sproutEmail()->notificationemail->getRecipientListsByNotificationId($notification->id);

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

		// Make sure to clear previous errors
		$notification->clearErrors();

		$notification->setAttributes($inputs);

		$this->notification = $notification;

		$this->validateAttribute('fromName', 'From Name', $inputs['fromName'], false);

		$this->validateAttribute('fromEmail', 'From Email', $inputs['fromEmail']);

		$this->validateAttribute('replyToEmail', 'Reply To', $inputs['replyToEmail']);

		$notification = $this->notification;
		// Do not clear errors to add additional validation
		if($notification->validate(null, false) && $notification->hasErrors() == false)
		{
			$fieldsLocation = craft()->request->getParam('fieldsLocation', 'fields');

			$notification->setContentFromPost($fieldsLocation);
			$notification->setContentPostLocation($fieldsLocation);

			$notification->getContent()->title = $notification->subjectLine;

			$result = sproutEmail()->notificationemail->saveNotification($notification);

			if ($result !== false)
			{
				craft()->userSession->setNotice(Craft::t('Notification saved.'));

				$_POST['redirect'] = str_replace('new', $notification->id, $_POST['redirect']);
			}
			else
			{
				craft()->userSession->setError(Craft::t('Unable to save notification.'));

				sproutEmail()->log($notification->getErrors());
			}

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

	private function validateAttribute($attribute, $label, $value, $email = true)
	{
		// Fix the &#8203 bug to test try the @asdf emails
		$value = filter_var($value, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);

		if (empty($value))
		{
			$this->notification->addError($attribute, Craft::t("$label cannot be blank."));
		}

		if ($email == true && filter_var($value, FILTER_VALIDATE_EMAIL) === false)
		{
		  $this->notification->addError($attribute, Craft::t("$label is not a valid email address."));
		}
	}

	private function returnErrors($notification)
	{
		craft()->userSession->setError(Craft::t('Unable to save notification email.'));

		craft()->urlManager->setRouteVariables(array(
			'notification' => $notification
		));
	}

	public function actionDeleteNotification()
	{
		$this->requirePostRequest();

		$inputs = craft()->request->getPost('sproutEmail');

		if (isset($inputs['id']))
		{
			$notificationId = $inputs['id'];

			$notification = craft()->elements->getElementById($notificationId);

			if (sproutEmail()->notificationemail->deleteNotificationById($notificationId))
			{
				$this->redirectToPostedUrl($notification);
			}
			else
			{
				// @TODO - return errors
				SproutEmailPlugin::log(json_encode($notification->getErrors()));
			}
		}
	}
}
