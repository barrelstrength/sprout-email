<?php
namespace Craft;

/**
 * Class SproutEmail_NotificationEmailsController
 *
 * @package Craft
 */
class SproutEmail_NotificationEmailsController extends BaseController
{

	private $notification;

	/**
	 * Renders the notification settings template with passed in route variables
	 *
	 * @param array $variables
	 *
	 * @throws HttpException
	 */
	public function actionNotificationSettingsTemplate(array $variables = array())
	{
		if (!sproutEmail()->checkPermission())
		{
			$this->redirect('sproutemail');
		}

		if (isset($variables['campaignId']))
		{
			if (!isset($variables['campaign']))
			{
				$variables['campaign'] = sproutEmail()->campaignTypes->getCampaignTypeById($variables['campaignId']);
			}
		}
		else
		{
			$variables['campaign'] = new SproutEmail_CampaignTypeModel();
		}

		$variables['isMailerInstalled'] = (bool) sproutEmail()->mailers->isInstalled('defaultmailer');

		$this->renderTemplate('sproutemail/settings/notifications/_edit', $variables);
	}
	
	public function actionEditNotificationSetting(array $variables = array())
	{
		$notificationId = null;

		$session = craft()->httpSession->get('newNotification');

		$notification = sproutEmail()->notificationEmails->getNotificationByVariables($variables, $session);

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

			$inputs['subjectLine'] = $inputs['name'];
			$inputs['slug']        = ElementHelper::createSlug($inputs['name']);
		}

		$notification->setAttributes($inputs);

		if ($notification->validate())
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
				// retain options attribute by the second parameter
				sproutEmail()->notificationEmails->saveNotification($notification, true);
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

		$notification = sproutEmail()->notificationEmails->getNotificationByVariables($variables, $session);

		$mailer         = sproutEmail()->mailers->getMailerByName('defaultmailer');
		$recipientLists = sproutEmail()->notificationEmails->getRecipientListsByNotificationId($notification->id);

		$showPreviewBtn = false;
		$shareUrl = null;

		if (!craft()->request->isMobileBrowser(true) && sproutEmail()->doesSiteTemplateExist($notification->template))
		{
			$showPreviewBtn = true;

			craft()->templates->includeJs(
				'Craft.LivePreview.init(' . JsonHelper::encode(
					array(
						'fields'        => '#subjectLine-field, #title-field, #fields > div > div > .field',
						'extraFields'   => '#settings',
						'previewUrl'    => $notification->getUrl(),
						'previewAction' => 'sproutEmail/notificationEmails/livePreviewNotificationEmail',
						'previewParams' => array(
							'notificationId' => $notification->id,
						)
					)
				) . ');'
			);

			$status = $notification->getStatus();

			$shareParams = array(
				'notificationId'    => $notification->id,
			);

			if ($notification->id && $notification->getUrl())
			{
				$shareUrl = UrlHelper::getActionUrl('sproutEmail/notificationEmails/shareNotificationEmail', $shareParams);
			}
		}

		$this->renderTemplate('sproutemail/notifications/_edit', array(
			'notification'   => $notification,
			'recipientLists' => $recipientLists,
			'mailer'         => $mailer,
			'showPreviewBtn' => $showPreviewBtn,
			'shareUrl'       => $shareUrl
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
			$session      = unserialize($session);
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

		$notification->subjectLine  = craft()->request->getRequiredPost('subjectLine');
		$notification->slug         = craft()->request->getRequiredPost('slug');

		if (empty($notification->slug))
		{
			$notification->slug = ElementHelper::createSlug($notification->subjectLine);
		}

		// Do not clear errors to add additional validation
		if ($notification->validate(null, false) && $notification->hasErrors() == false)
		{
			$fieldsLocation = craft()->request->getParam('fieldsLocation', 'fields');

			$notification->setContentFromPost($fieldsLocation);
			$notification->setContentPostLocation($fieldsLocation);

			$notification->getContent()->title = $notification->subjectLine;

			$result = sproutEmail()->notificationEmails->saveNotification($notification);

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

	public function actionDeleteNotificationEmail()
	{
		$this->requirePostRequest();

		$inputs = craft()->request->getPost('sproutEmail');

		if (isset($inputs['id']))
		{
			$notificationId = $inputs['id'];

			$notification = craft()->elements->getElementById($notificationId);

			if (sproutEmail()->notificationEmails->deleteNotificationEmailById($notificationId))
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

	public function actionGetPrepareModal()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$notificationId = craft()->request->getRequiredPost('notificationId');

		$response = sproutEmail()->notificationEmails->getPrepareModal($notificationId);

		$this->returnJson($response->getAttributes());
	}

	/**
	 * @throws HttpException
	 */
	public function actionExport()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$notificationEmail = craft()->elements->getElementById(craft()->request->getPost('notificationId'));

		if ($notificationEmail)
		{
			try
			{
				$response = sproutEmail()->notificationEmails->exportEmail($notificationEmail);

				if ($response instanceof SproutEmail_ResponseModel)
				{
					if ($response->success == true)
					{
						if ($response->emailModel != null)
						{
							$emailModel = $response->emailModel;

							$event = new Event($this, array(
								'notificationModel' => $notificationEmail,
								'emailModel'        => $emailModel
							));

							sproutEmail()->onSendSproutEmail($event);
						}
					}

					$this->returnJson($response);
				}

				$errorMessage = Craft::t('Mailer did not return a valid response model after notification email export.');

				if (!$response)
				{
					$errorMessage = Craft::t('Unable to send email.');
				}

				$this->returnJson(
					SproutEmail_ResponseModel::createErrorModalResponse('sproutemail/_modals/export', array(
						'email'    => $notificationEmail,
						'campaign' => $campaign,
						'message'  => $errorMessage
					))
				);

			}
			catch (\Exception $e)
			{
				$this->returnJson(
					SproutEmail_ResponseModel::createErrorModalResponse('sproutemail/_modals/export', array(
						'email'    => $notificationEmail,
						'campaign' => $campaign,
						'message'  => $e->getMessage(),
					))
				);
			}
		}

		$this->returnJson(
			SproutEmail_ResponseModel::createErrorModalResponse('sproutemail/_modals/export', array(
				'email'    => $notificationEmail,
				'campaign' => !empty($campaign) ? $campaign : null,
				'message'  => Craft::t('The campaign email you are trying to send is missing.'),
			))
		);
	}

	public function actionShareNotificationEmail($notificationId = null)
	{
		if ($notificationId)
		{
			$notificationEmail = sproutEmail()->notificationEmails->getNotificationEmailById($notificationId);

			if (!$notificationEmail)
			{
				throw new HttpException(404);
			}

			$params = array(
				'notificationId' => $notificationId,
			);
		}
		else
		{
			throw new HttpException(404);
		}

		// Create the token and redirect to the entry URL with the token in place
		$token = craft()->tokens->createToken(
			array(
				'action' => 'sproutEmail/notificationEmails/viewSharedNotificationEmail',
				'params' => $params
			)
		);

		$url = UrlHelper::getUrlWithToken($notificationEmail->getUrl(), $token);

		craft()->request->redirect($url);
	}

	public function actionLivePreviewNotificationEmail()
	{
		$notificationId = craft()->request->getPost('notificationId');

		sproutEmail()->notificationEmails->getPreviewNotificationEmailById($notificationId);
	}

	public function actionViewSharedNotificationEmail($notificationId = null)
	{
		$this->requireToken();

		sproutEmail()->notificationEmails->getPreviewNotificationEmailById($notificationId);
	}
}
