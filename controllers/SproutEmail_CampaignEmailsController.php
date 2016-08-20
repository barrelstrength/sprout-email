<?php
namespace Craft;

/**
 * Class SproutEmail_CampaignEmailsController
 *
 * @package Craft
 */
class SproutEmail_CampaignEmailsController extends BaseController
{
	/**
	 * List of actions allowed to be called from outside the Control Panel
	 *
	 * @var array
	 */
	protected $allowAnonymous = array('actionViewSharedCampaignEmail');

	/**
	 * The Campaign Type that this Campaign Email is associated with
	 *
	 * @var SproutEmail_CampaignTypeModel
	 */
	protected $campaignType;

	/**
	 * Renders Campaign Email Edit Template
	 *
	 * @param array $variables
	 *
	 * @throws HttpException
	 * @throws Exception
	 */
	public function actionEditCampaignEmailTemplate(array $variables = array())
	{
		$emailId        = craft()->request->getSegment(4);
		$campaignTypeId = craft()->request->getSegment(3);
		$showPreviewBtn = false;
		$shareUrl       = null;

		$campaignEmail = isset($variables['campaignEmail']) ? $variables['campaignEmail'] : null;

		// Check if we already have an Campaign Email route variable
		// If so it's probably due to a bad form submission and has an error object
		// that we don't want to overwrite.
		if (!$campaignEmail)
		{
			if (is_numeric($emailId))
			{
				$campaignEmail = craft()->elements->getElementById($emailId);
			}
			else
			{
				$campaignEmail = new SproutEmail_CampaignEmailModel();
			}
		}

		if (!is_numeric($campaignTypeId))
		{
			$campaignTypeId = $campaignEmail->campaignTypeId;
		}

		$campaignType = sproutEmail()->campaignTypes->getCampaignTypeById($campaignTypeId);

		$isMobileBrowser    = craft()->request->isMobileBrowser(true);
		$siteTemplateExists = sproutEmail()->doesSiteTemplateExist($campaignType->template);

		// Enable Live Preview?
		if (!$isMobileBrowser && $siteTemplateExists)
		{
			craft()->templates->includeJs(
				'Craft.LivePreview.init(' . JsonHelper::encode(
					array(
						'fields'        => '#subjectLine-field, #title-field, #fields > div > div > .field',
						'extraFields'   => '#settings',
						'previewUrl'    => $campaignEmail->getUrl(),
						'previewAction' => 'sproutEmail/campaignEmails/livePreviewCampaignEmail',
						'previewParams' => array(
							'emailId'        => $campaignEmail->id,
							'campaignTypeId' => $campaignType->id,
						)
					)
				) . ');'
			);

			// Should we show the Share button too?
			if ($campaignEmail->id && $campaignEmail->getUrl())
			{
				$showPreviewBtn = true;

				$status = $campaignEmail->getStatus();

				if ($status != 'ready')
				{
					$shareUrl = UrlHelper::getActionUrl('sproutEmail/campaignEmails/shareCampaignEmail', array(
						'emailId'        => $campaignEmail->id,
						'campaignTypeId' => $campaignType->id
					));
				}
				else
				{
					$shareUrl = $campaignEmail->getUrl();
				}
			}
		}

		$recipientLists = sproutEmail()->campaignEmails->getRecipientListsByEmailId($emailId);

		$this->renderTemplate('sproutemail/campaigns/_edit', array(
			'emailId'        => $emailId,
			'campaignEmail'  => $campaignEmail,
			'campaignTypeId' => $campaignTypeId,
			'campaignType'   => $campaignType,
			'showPreviewBtn' => $showPreviewBtn,
			'shareUrl'       => $shareUrl,
			'recipientLists' => $recipientLists
		));
	}

	/**
	 * Saves a Campaign Email
	 *
	 * @throws Exception
	 * @throws HttpException
	 * @return void
	 */
	public function actionSaveCampaignEmail()
	{
		$this->requirePostRequest();

		$campaignTypeId     = craft()->request->getRequiredPost('campaignTypeId');
		$this->campaignType = sproutEmail()->campaignTypes->getCampaignTypeById($campaignTypeId);

		if (!$this->campaignType)
		{
			throw new Exception(Craft::t('No Campaign exists with the id “{id}”', array(
				'id' => $campaignTypeId
			)));
		}

		$campaignEmail = $this->getCampaignEmailModel();
		$campaignEmail = $this->populateCampaignEmailModel($campaignEmail);

		if (craft()->request->getPost('saveAsNew'))
		{
			$campaignEmail->saveAsNew = true;
			$campaignEmail->id        = null;
		}

		if ($titleFormat = $this->campaignType->titleFormat)
		{
			$title = craft()->templates->renderObjectTemplate($titleFormat, $campaignEmail);

			$campaignEmail->getContent()->title = $title;
		}

		if (sproutEmail()->campaignEmails->saveCampaignEmail($campaignEmail, $this->campaignType))
		{
			craft()->userSession->setNotice(Craft::t('Campaign Email saved.'));

			$_POST['redirect'] = str_replace('{id}', $campaignEmail->id, $_POST['redirect']);

			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Could not save Campaign Email.'));

			craft()->urlManager->setRouteVariables(array(
				'campaignEmail' => $campaignEmail
			));
		}
	}

	/**
	 * Delete a Campaign Email
	 *
	 * @return void
	 */
	public function actionDeleteCampaignEmail()
	{
		$this->requirePostRequest();

		// Get the Campaign Email
		$emailId       = craft()->request->getRequiredPost('emailId');
		$campaignEmail = sproutEmail()->campaignEmails->getCampaignEmailById($emailId);

		if (sproutEmail()->campaignEmails->deleteCampaignEmail($campaignEmail))
		{
			$this->redirectToPostedUrl($campaignEmail);
		}
		else
		{
			// @TODO - return errors
			SproutEmailPlugin::log(json_encode($campaignEmail->getErrors()));
		}
	}

	/**
	 * Gives a mailer the ability to relay method calls to itself from a modal window
	 *
	 * @throws HttpException
	 */
	public function actionRelayMailerMethod()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$response = array(
			'success' => false,
			'content' => '',
			'message' => '',
		);

		$mailer = craft()->request->getPost('mailer');
		$method = craft()->request->getPost('method');

		if (!$mailer)
		{
			$response['message'] = Craft::t('The mailer name is required.');

			$this->returnJson($response);
		}

		$mailer = sproutEmail()->mailers->getMailerByName($mailer);

		if (!$mailer)
		{
			$response['message'] = Craft::t('The {name} mailer could not be instantiated.');

			$this->returnJson($response);
		}

		if (!$method || !method_exists($mailer, $method))
		{
			$response['message'] = Craft::t('You forgot to pass in a method to call or that method does not exist.');

			$this->returnJson($response);
		}

		try
		{
			$result = call_user_func(array($mailer, $method));

			if (empty($result['content']))
			{
				$response['message'] = Craft::t('You did not return any content from {method}().', array(
					'method' => $method
				));

				$this->returnJson($response);
			}

			$this->returnJson($result);
		}
		catch (\Exception $e)
		{
			$response['message'] = Craft::t($e->getMessage());

			$this->returnJson($response);
		}
	}

	/**
	 * Send a Campaign Email via a Mailer
	 *
	 * @throws HttpException
	 */
	public function actionSendCampaignEmail()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$emailId = craft()->request->getPost('emailId');

		$campaignEmail = sproutEmail()->campaignEmails->getCampaignEmailById($emailId);
		$campaignType  = sproutEmail()->campaignTypes->getCampaignTypeById($campaignEmail->campaignTypeId);

		if ($campaignEmail && $campaignType)
		{
			try
			{
				$response = sproutEmail()->mailers->sendCampaignEmail($campaignEmail, $campaignType);

				if ($response instanceof SproutEmail_ResponseModel)
				{
					if ($response->success == true)
					{
						if ($response->emailModel != null)
						{
							$emailModel = $response->emailModel;

							$event = new Event($this, array(
								'entryModel' => $campaignEmail,
								'emailModel' => $emailModel,
								'campaign'   => $campaignType
							));

							sproutEmail()->onSendSproutEmail($event);
						}
					}

					$this->returnJson($response);
				}

				$errorMessage = Craft::t('Mailer did not return a valid response model after Campaign Email export.');

				if (!$response)
				{
					$errorMessage = Craft::t('Unable to send email.');
				}

				$this->returnJson(
					SproutEmail_ResponseModel::createErrorModalResponse(
						'sproutemail/_modals/export',
						array(
							'email'    => $campaignEmail,
							'campaign' => $campaignType,
							'message'  => Craft::t($errorMessage),
						)
					)
				);
			}
			catch (\Exception $e)
			{
				$this->returnJson(
					SproutEmail_ResponseModel::createErrorModalResponse(
						'sproutemail/_modals/export',
						array(
							'email'    => $campaignEmail,
							'campaign' => $campaignType,
							'message'  => Craft::t($e->getMessage()),
						)
					)
				);
			}
		}

		$this->returnJson(
			SproutEmail_ResponseModel::createErrorModalResponse(
				'sproutemail/_modals/export',
				array(
					'email'    => $campaignEmail,
					'campaign' => !empty($campaignType) ? $campaignType : null,
					'message'  => Craft::t('The campaign email you are trying to send is missing.'),
				)
			)
		);
	}

	/**
	 * Redirects the client to a URL for viewing an entry/draft on the front end.
	 *
	 * @param mixed $emailId
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function actionShareCampaignEmail($emailId = null)
	{
		if ($emailId)
		{
			$campaignEmail = sproutEmail()->campaignEmails->getCampaignEmailById($emailId);

			if (!$campaignEmail)
			{
				throw new HttpException(404);
			}
		}
		else
		{
			throw new HttpException(404);
		}

		// Create the token and redirect to the entry URL with the token in place
		$token = craft()->tokens->createToken(array(
			'action' => 'sproutEmail/campaignEmails/viewSharedCampaignEmail',
			'params' => array(
				'emailId' => $emailId
			)
		));

		$url = UrlHelper::getUrlWithToken($campaignEmail->getUrl(), $token);

		craft()->request->redirect($url);
	}

	/**
	 * Prepare the viewing of a shared Campaign Email
	 *
	 * @param null $emailId
	 * @param null $type
	 *
	 * @throws HttpException
	 */
	public function actionViewSharedCampaignEmail($emailId = null, $type = null)
	{
		$this->requireToken();

		if ($campaignEmail = sproutEmail()->campaignEmails->getCampaignEmailById($emailId))
		{
			$campaignType = sproutEmail()->campaignTypes->getCampaignTypeById($campaignEmail->campaignTypeId);

			$object    = null;
			$email     = new EmailModel();
			$template  = $campaignType->template;
			$extension = ($type != null && $type == 'text') ? 'txt' : 'html';

			$email = sproutEmail()->defaultmailer->renderEmailTemplates($email, $template, $campaignEmail, $object);

			sproutEmail()->campaignEmails->showCampaignEmail($email, $extension);
		}

		throw new HttpException(404);
	}

	/**
	 * Prepare Live Preview for Campaign Email
	 *
	 * @throws HttpException
	 */
	public function actionLivePreviewCampaignEmail()
	{
		$emailId = craft()->request->getPost('emailId');

		if ($emailId)
		{
			$campaignEmail = sproutEmail()->campaignEmails->getCampaignEmailById($emailId);

			if (!$campaignEmail)
			{
				throw new HttpException(404);
			}
		}
		else
		{
			$campaignTypeId = craft()->request->getPost('campaignTypeId');

			$campaignEmail                 = new SproutEmail_CampaignEmailModel();
			$campaignEmail->campaignTypeId = $campaignTypeId;
		}

		$campaignEmail->subjectLine         = craft()->request->getPost('subjectLine', $campaignEmail->subjectLine);
		$campaignEmail->getContent()->title = $campaignEmail->subjectLine;

		$fieldsLocation = craft()->request->getParam('fieldsLocation', 'fields');

		$campaignEmail->setContentFromPost($fieldsLocation);

		// Prepare variables to render email templates
		// -------------------------------------------
		$campaignType = sproutEmail()->campaignTypes->getCampaignTypeById($campaignEmail->campaignTypeId);

		$email    = new EmailModel();
		$object   = null;
		$template = $campaignType->template;

		$email = sproutEmail()->defaultmailer->renderEmailTemplates($email, $template, $campaignEmail, $object);

		sproutEmail()->campaignEmails->showCampaignEmail($email);
	}

	/**
	 * Fetch or create a SproutEmail_CampaignEmailModel
	 *
	 * @throws Exception
	 * @return SproutEmail_CampaignEmailModel
	 */
	protected function getCampaignEmailModel()
	{
		$emailId   = craft()->request->getPost('emailId');
		$saveAsNew = craft()->request->getPost('saveAsNew');

		if ($emailId && !$saveAsNew && $emailId != 'new')
		{
			$campaignEmail = sproutEmail()->campaignEmails->getCampaignEmailById($emailId);

			if (!$campaignEmail)
			{
				throw new Exception(Craft::t('No entry exists with the ID “{id}”', array('id' => $emailId)));
			}
		}
		else
		{
			$campaignEmail = new SproutEmail_CampaignEmailModel();
		}

		return $campaignEmail;
	}

	/**
	 * Populates a SproutEmail_CampaignEmailModel with post data
	 *
	 * @param SproutEmail_CampaignEmailModel $campaignEmail
	 *
	 * @return SproutEmail_CampaignEmailModel
	 * @throws HttpException
	 */
	protected function populateCampaignEmailModel(SproutEmail_CampaignEmailModel $campaignEmail)
	{
		$campaignEmail->campaignTypeId = $this->campaignType->id;
		$campaignEmail->slug           = craft()->request->getPost('slug', $campaignEmail->slug);
		$campaignEmail->enabled        = (bool) craft()->request->getPost('enabled', $campaignEmail->enabled);
		$campaignEmail->fromName       = craft()->request->getPost('sproutEmail.fromName');
		$campaignEmail->fromEmail      = craft()->request->getPost('sproutEmail.fromEmail');
		$campaignEmail->replyToEmail   = craft()->request->getPost('sproutEmail.replyToEmail');
		$campaignEmail->subjectLine    = craft()->request->getRequiredPost('subjectLine');

		$enableFileAttachments                = craft()->request->getPost('sproutEmail.enableFileAttachments');
		$campaignEmail->enableFileAttachments = $enableFileAttachments ? $enableFileAttachments : false;

		$campaignEmail->getContent()->title = $campaignEmail->subjectLine;

		if (empty($campaignEmail->slug))
		{
			$campaignEmail->slug = ElementHelper::createSlug($campaignEmail->subjectLine);
		}

		$fieldsLocation = craft()->request->getParam('fieldsLocation', 'fields');

		$campaignEmail->setContentFromPost($fieldsLocation);
		$campaignEmail->setContentPostLocation($fieldsLocation);

		return $campaignEmail;
	}
}
