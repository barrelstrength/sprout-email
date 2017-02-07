<?php
namespace Craft;

/**
 * Class SproutEmail_NotificationEmailsController
 *
 * @package Craft
 */
class SproutEmail_NotificationEmailsController extends BaseController
{
	/**
	 * @var
	 */
	private $notification;

	/**
	 * Renders the Edit Notification Email template
	 *
	 * @param array $variables
	 */
	public function actionEditNotificationEmailTemplate(array $variables = array())
	{
		$notificationId    = isset($variables['notificationId']) ? $variables['notificationId'] : null;
		$notificationEmail = isset($variables['notificationEmail']) ? $variables['notificationEmail'] : null;

		if (!$notificationEmail)
		{
			$notificationEmail = sproutEmail()->notificationEmails->getNotificationEmailById($notificationId);
		}

		$mailer = sproutEmail()->mailers->getMailerByName('defaultmailer');

		$recipientLists = sproutEmail()->campaignEmails->getRecipientListsByEmailId($notificationEmail->id);

		$showPreviewBtn = false;
		$shareUrl       = null;

		$isMobileBrowser    = craft()->request->isMobileBrowser(true);
		$siteTemplateExists = sproutEmail()->doesSiteTemplateExist($notificationEmail->template);

		if (!$isMobileBrowser && $siteTemplateExists)
		{
			$showPreviewBtn = true;

			craft()->templates->includeJs(
				'Craft.LivePreview.init(' . JsonHelper::encode(
					array(
						'fields'        => '#subjectLine-field, #title-field, #fields > div > div > .field',
						'extraFields'   => '#settings',
						'previewUrl'    => $notificationEmail->getUrl(),
						'previewAction' => 'sproutEmail/notificationEmails/livePreviewNotificationEmail',
						'previewParams' => array(
							'notificationId' => $notificationEmail->id,
						)
					)
				) . ');'
			);

			if ($notificationEmail->id && $notificationEmail->getUrl())
			{
				$shareUrl = UrlHelper::getActionUrl('sproutEmail/notificationEmails/shareNotificationEmail', array(
					'notificationId' => $notificationEmail->id,
				));
			}
		}

		$this->renderTemplate('sproutemail/notifications/_edit', array(
			'notificationEmail' => $notificationEmail,
			'recipientLists'    => $recipientLists,
			'mailer'            => $mailer,
			'showPreviewBtn'    => $showPreviewBtn,
			'shareUrl'          => $shareUrl
		));
	}

	/**
	 * Renders the Edit Notification Email Settings template
	 *
	 * @param array $variables
	 *
	 * @throws HttpException
	 */
	public function actionEditNotificationEmailSettingsTemplate(array $variables = array())
	{
		$currentUser = craft()->userSession->getUser();

		if (!$currentUser->can('editSproutEmailSettings'))
		{
			$this->redirect('sproutemail');
		}

		$emailId                = isset($variables['emailId']) ? $variables['emailId'] : null;
		$notificationEmail      = isset($variables['notificationEmail']) ? $variables['notificationEmail'] : null;
		$isMailerInstalled      = (bool) sproutEmail()->mailers->isInstalled('defaultmailer');
		$isNewNotificationEmail = isset($emailId) && $emailId == 'new' ? true : false;

		if (!$notificationEmail)
		{
			if (!$isNewNotificationEmail)
			{
				$notificationEmail = sproutEmail()->notificationEmails->getNotificationEmailById($emailId);
			}
			else
			{
				$notificationEmail = new SproutEmail_NotificationEmailModel();
			}
		}

		$this->renderTemplate('sproutemail/settings/notifications/_edit', array(
			'emailId'                => $emailId,
			'notificationEmail'      => $notificationEmail,
			'isMailerInstalled'      => $isMailerInstalled,
			'isNewNotificationEmail' => $isNewNotificationEmail
		));
	}

	/**
	 * Save a Notification Email from the Notification Email template
	 */
	public function actionSaveNotificationEmail()
	{
		$this->requirePostRequest();

		$fields = craft()->request->getPost('sproutEmail');

		if (isset($fields['id']))
		{
			$notificationId = $fields['id'];

			$notificationEmail = sproutEmail()->notificationEmails->getNotificationEmailById($notificationId);
		}

		$notificationEmail->clearErrors();

		$notificationEmail->setAttributes($fields);

		$this->notification = $notificationEmail;

		$this->validateAttribute('fromName', 'From Name', $fields['fromName']);

		$this->validateAttribute('fromEmail', 'From Email', $fields['fromEmail']);

		$this->validateAttribute('replyToEmail', 'Reply To', $fields['replyToEmail']);

		$notificationEmail = $this->notification;

		$notificationEmail->subjectLine = craft()->request->getRequiredPost('subjectLine');
		$notificationEmail->slug        = craft()->request->getRequiredPost('slug');
		$notificationEmail->enabled     = craft()->request->getRequiredPost('enabled');

		if (empty($notificationEmail->slug))
		{
			$notificationEmail->slug = ElementHelper::createSlug($notificationEmail->subjectLine);
		}

		$fieldsLocation = craft()->request->getParam('fieldsLocation', 'fields');

		$notificationEmail->setContentFromPost($fieldsLocation);
		$notificationEmail->setContentPostLocation($fieldsLocation);

		// Do not clear errors to add additional validation
		if ($notificationEmail->validate(null, false) && $notificationEmail->hasErrors() == false)
		{
			$notificationEmail->getContent()->title = $notificationEmail->subjectLine;

			$result = sproutEmail()->notificationEmails->saveNotification($notificationEmail);

			if ($result !== false)
			{
				if (craft()->plugins->getPlugin('sproutlists') != null)
				{
					$sproutLists = craft()->request->getPost('sproutlists');

					sproutLists()->addSyncElement($sproutLists, $notificationEmail->id);
				}

				craft()->userSession->setNotice(Craft::t('Notification saved.'));
			}
			else
			{
				craft()->userSession->setError(Craft::t('Unable to save notification.'));

				sproutEmail()->log($notificationEmail->getErrors());
			}

			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Unable to save notification email.'));

			craft()->urlManager->setRouteVariables(array(
				'notificationEmail' => $notificationEmail
			));
		}
	}

	/**
	 * Save a Notification Email from the Notification Email Settings template
	 */
	public function actionSaveNotificationEmailSettings()
	{
		$this->requirePostRequest();

		$emailId                = craft()->request->getPost('emailId');
		$fields                 = craft()->request->getPost('sproutEmail');
		$isNewNotificationEmail = isset($emailId) && $emailId == 'new' ? true : false;

		if (!$isNewNotificationEmail)
		{
			$notificationEmail = craft()->elements->getElementById($emailId);
		}
		else
		{
			$notificationEmail = new SproutEmail_NotificationEmailModel();

			$notificationEmail->getContent()->title = $fields['subjectLine'];

			$fields['subjectLine'] = $fields['name'];
			$fields['slug']        = ElementHelper::createSlug($fields['name']);
		}

		$notificationEmail->setAttributes($fields);

		if ($notificationEmail->validate())
		{
			if (craft()->request->getPost('fieldLayout'))
			{
				// Set the field layout
				$fieldLayout = craft()->fields->assembleLayoutFromPost();

				$fieldLayout->type = 'SproutEmail_NotificationEmail';

				$notificationEmail->setFieldLayout($fieldLayout);

				if ($notificationEmail->fieldLayoutId != null)
				{
					// Remove previous field layout
					craft()->fields->deleteLayoutById($notificationEmail->fieldLayoutId);
				}

				craft()->fields->saveLayout($fieldLayout);
			}

			// retain options attribute by the second parameter
			sproutEmail()->notificationEmails->saveNotification($notificationEmail, true);

			$this->redirectToPostedUrl($notificationEmail);
		}
		else
		{
			craft()->userSession->setError(Craft::t('Unable to save setting.'));

			craft()->urlManager->setRouteVariables(array(
				'notificationEmail' => $notificationEmail
			));
		}
	}

	/**
	 * Delete a Notification Email
	 */
	public function actionDeleteNotificationEmail()
	{
		$this->requirePostRequest();

		$notificationId = craft()->request->getRequiredPost('sproutEmail.id');

		if ($notificationId)
		{
			$notificationEmail = sproutEmail()->notificationEmails->getNotificationEmailById($notificationId);

			if (sproutEmail()->notificationEmails->deleteNotificationEmailById($notificationId))
			{
				$this->redirectToPostedUrl($notificationEmail);
			}
			else
			{
				// @TODO - return errors
				SproutEmailPlugin::log(json_encode($notificationEmail->getErrors()), LogLevel::Error);
			}
		}
	}

	/**
	 * Send a notification email via a Mailer
	 *
	 * @throws HttpException
	 */
	public function actionSendNotificationEmail()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$notificationId    = craft()->request->getPost('notificationId');
		$notificationEmail = craft()->elements->getElementById($notificationId);

		if ($notificationEmail)
		{
			try
			{
				$response = sproutEmail()->notificationEmails->sendNotificationEmail($notificationEmail);

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
					SproutEmail_ResponseModel::createErrorModalResponse('sproutemail/_modals/sendEmailConfirmation', array(
						'email'   => $notificationEmail,
						'message' => $errorMessage
					))
				);
			}
			catch (\Exception $e)
			{
				$this->returnJson(
					SproutEmail_ResponseModel::createErrorModalResponse('sproutemail/_modals/sendEmailConfirmation', array(
						'email'   => $notificationEmail,
						'message' => $e->getMessage(),
					))
				);
			}
		}

		$this->returnJson(
			SproutEmail_ResponseModel::createErrorModalResponse('sproutemail/_modals/sendEmailConfirmation', array(
				'email'    => $notificationEmail,
				'campaign' => !empty($campaign) ? $campaign : null,
				'message'  => Craft::t('The notification email you are trying to send is missing.'),
			))
		);
	}

	/**
	 * Provides a way for mailers to render content to perform actions inside a a modal window
	 */
	public function actionGetPrepareModal()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$notificationId = craft()->request->getRequiredPost('notificationId');

		$response = sproutEmail()->notificationEmails->getPrepareModal($notificationId);

		$this->returnJson($response->getAttributes());
	}

	/**
	 * Prepares a Notification Email to be shared via token-based URL
	 *
	 * @param null $notificationId
	 *
	 * @throws HttpException
	 */
	public function actionShareNotificationEmail($notificationId = null)
	{
		if ($notificationId)
		{
			$notificationEmail = sproutEmail()->notificationEmails->getNotificationEmailById($notificationId);

			if (!$notificationEmail)
			{
				throw new HttpException(404);
			}

			$type = craft()->request->getQuery('type');

			$params = array(
				'notificationId' => $notificationId,
				'type'           => $type
			);
		}
		else
		{
			throw new HttpException(404);
		}

		// Create the token and redirect to the entry URL with the token in place
		$token = craft()->tokens->createToken(array(
			'action' => 'sproutEmail/notificationEmails/viewSharedNotificationEmail',
			'params' => $params
		));

		$url = UrlHelper::getUrlWithToken($notificationEmail->getUrl(), $token);

		craft()->request->redirect($url);
	}

	/**
	 * Renders a shared Notification Email
	 *
	 * @param null $notificationId
	 * @param null $type
	 */
	public function actionViewSharedNotificationEmail($notificationId = null, $type = null)
	{
		$this->requireToken();

		sproutEmail()->notificationEmails->getPreviewNotificationEmailById($notificationId, $type);
	}

	/**
	 * Renders a Notification Email for Live Preview
	 */
	public function actionLivePreviewNotificationEmail()
	{
		$notificationId = craft()->request->getPost('notificationId');

		sproutEmail()->notificationEmails->getPreviewNotificationEmailById($notificationId);
	}

	/**
	 * Validate a Notification Email attribute and add errors to the model
	 *
	 * @param      $attribute
	 * @param      $label
	 * @param      $value
	 * @param bool $email
	 */
	private function validateAttribute($attribute, $label, $value, $email = false)
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
}
