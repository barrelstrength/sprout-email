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
	 * The Campaign that this Campaign Email is associated with if any
	 *
	 * @var SproutEmail_CampaignTypeModel
	 */
	protected $campaign;

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

		$campaignId     = craft()->request->getRequiredPost('campaignId');
		$this->campaign = sproutEmail()->campaignTypes->getCampaignTypeById($campaignId);

		if (!$this->campaign)
		{
			throw new Exception(Craft::t('No Campaign exists with the id “{id}”', array(
				'id' => $campaignId
			)));
		}

		$campaignEmail = $this->getCampaignEmailModel();
		$campaignEmail = $this->populateCampaignEmailModel($campaignEmail);

		if (craft()->request->getPost('saveAsNew'))
		{
			$campaignEmail->saveAsNew = true;
			$campaignEmail->id        = null;
		}

		if ($this->campaign->titleFormat)
		{
			$campaignEmail->getContent()->title = craft()->templates->renderObjectTemplate($this->campaign->titleFormat, $campaignEmail);
		}

		if (sproutEmail()->campaignEmails->saveCampaignEmail($campaignEmail, $this->campaign))
		{
			craft()->userSession->setNotice(Craft::t('Campaign Email saved.'));

			$_POST['redirect'] = str_replace('{id}', $campaignEmail->id, $_POST['redirect']);

			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Could not save Campaign Email.'));

			craft()->urlManager->setRouteVariables(array(
				'email' => $campaignEmail
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
		$emailId = craft()->request->getRequiredPost('emailId');
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
	 * @throws HttpException
	 */
	public function actionExport()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$campaignEmail = sproutEmail()->campaignEmails->getCampaignEmailById(craft()->request->getPost('emailId'));

		if ($campaignEmail && ($campaign = sproutEmail()->campaignTypes->getCampaignTypeById($campaignEmail->campaignId)))
		{
			try
			{
				$response = sproutEmail()->mailers->exportEmail($campaignEmail, $campaign);

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
								'campaign'   => $campaign
							));

							sproutEmail()->onSendCampaign($event);
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
							'campaign' => $campaign,
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
							'campaign' => $campaign,
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
					'campaign' => !empty($campaign) ? $campaign : null,
					'message'  => Craft::t('The campaign email you are trying to send is missing.'),
				)
			)
		);
	}

	/**
	 * Route Controller for Edit Campaign Email Template
	 *
	 * @param array $variables
	 *
	 * @throws HttpException
	 * @throws Exception
	 */
	public function actionEditCampaignEmailTemplate(array $variables = array())
	{
		$emailId    = craft()->request->getSegment(4);
		$campaignId = craft()->request->getSegment(3);

		// Check if we already have an Campaign Email route variable
		// If so it's probably due to a bad form submission and has an error object
		// that we don't want to overwrite.
		if (!isset($variables['email']))
		{
			if (is_numeric($emailId))
			{
				$variables['email']   = craft()->elements->getElementById($emailId);
				$variables['emailId'] = $emailId;
			}
			else
			{
				$variables['email'] = new SproutEmail_CampaignEmailModel();
			}
		}

		if (!is_numeric($campaignId))
		{
			$campaignId = $variables['email']->campaignId;
		}

		$variables['campaign']   = sproutEmail()->campaignTypes->getCampaignTypeById($campaignId);
		$variables['campaignId'] = $campaignId;

		// Enable Live Preview?
		if (!craft()->request->isMobileBrowser(true) && sproutEmail()->doesSiteTemplateExist($variables['campaign']->template))
		{
			craft()->templates->includeJs(
				'Craft.LivePreview.init(' . JsonHelper::encode(
					array(
						'fields'        => '#subjectLine-field, #title-field, #fields > div > div > .field',
						'extraFields'   => '#settings',
						'previewUrl'    => $variables['email']->getUrl(),
						'previewAction' => 'sproutEmail/campaignEmails/livePreviewCampaignEmail',
						'previewParams' => array(
							'emailId'    => $variables['email']->id,
							'campaignId' => $variables['campaign']->id,
						)
					)
				) . ');'
			);

			$variables['showPreviewBtn'] = true;

			$shareParams = array(
				'emailId'    => $variables['email']->id,
				'campaignId' => $variables['campaign']->id
			);

			$status = $variables['email']->getStatus();

			// Should we show the Share button too?
			if ($variables['email']->id && $variables['email']->getUrl())
			{
				if ($status != 'ready')
				{
					$variables['shareUrl'] = UrlHelper::getActionUrl('sproutEmail/campaignEmails/shareCampaignEmail', $shareParams);
				}
				else
				{
					$variables['shareUrl'] = $variables['email']->getUrl();
				}
			}
		}
		else
		{
			$variables['showPreviewBtn'] = false;
		}

		$variables['recipientLists'] = sproutEmail()->campaignEmails->getRecipientListsByEmailId($emailId);

		$this->renderTemplate('sproutemail/campaigns/_edit', $variables);
	}

	public function actionPreview()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$campaignEmail = sproutEmail()->campaignEmails->getCampaignEmailById(craft()->request->getPost('emailId'));

		if ($campaignEmail && ($campaign = sproutEmail()->campaignTypes->getCampaignTypeById($campaignEmail->campaignId)))
		{
			try
			{
				$result = sproutEmail()->mailers->previewCampaignEmail($campaignEmail, $campaign);

				if (craft()->request->isAjaxRequest())
				{
					return $result['content'];
				}
				craft()->end();
			}
			catch (\Exception $e)
			{
				sproutEmail()->error($e->getMessage());
			}
		}
		else
		{
			throw new Exception(Craft::t('Campaign Email or Campaign are missing'));
		}
	}

	/**
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
			$campaignId = craft()->request->getPost('campaignId');

			$campaignEmail             = new SproutEmail_CampaignEmailModel();
			$campaignEmail->campaignId = $campaignId;
		}

		$campaignEmail->subjectLine         = craft()->request->getPost('subjectLine', $campaignEmail->subjectLine);
		$campaignEmail->getContent()->title = $campaignEmail->subjectLine;

		$fieldsLocation = craft()->request->getParam('fieldsLocation', 'fields');

		$campaignEmail->setContentFromPost($fieldsLocation);

		// Prepare variables to render email templates
		// -------------------------------------------
		$campaign = sproutEmail()->campaignTypes->getCampaignTypeById($campaignEmail->campaignId);

		$object = null;



		// Create an Email so we can render our template
		$email = new EmailModel();

		$template = $campaign->template;

		$email = sproutEmail()->defaultmailer->renderEmailTemplates($email, $template, $campaignEmail, $object);

		sproutEmail()->campaignEmails->showBufferCampaignEmail($email);
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

			$params = array(
				'emailId' => $emailId,
			);
		}
		else
		{
			throw new HttpException(404);
		}

		// Create the token and redirect to the entry URL with the token in place
		$token = craft()->tokens->createToken(
			array(
				'action' => 'sproutEmail/campaignEmails/viewSharedCampaignEmail',
				'params' => $params
			)
		);

		$url = UrlHelper::getUrlWithToken($campaignEmail->getUrl(), $token);

		craft()->request->redirect($url);
	}

	/**
	 * @param null $emailId
	 *
	 * @param null $template
	 *
	 * @throws HttpException
	 */
	public function actionViewSharedCampaignEmail($emailId = null, $template = null)
	{
		$this->requireToken();

		if ($campaignEmail = sproutEmail()->campaignEmails->getCampaignEmailById($emailId))
		{
			$campaign = sproutEmail()->campaignTypes->getCampaignTypeById($campaignEmail->campaignId);

			$object = null;


			// Create an Email so we can render our template
			$email = new EmailModel();

			$template = $campaign->template;

			$email = sproutEmail()->defaultmailer->renderEmailTemplates($email, $template, $campaignEmail, $object);

			sproutEmail()->campaignEmails->showBufferCampaignEmail($email);
		}
		else
		{
			throw new HttpException(404);
		}
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

		if ($emailId && !$saveAsNew)
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
		$campaignEmail->campaignId   = $this->campaign->id;
		$campaignEmail->slug         = craft()->request->getPost('slug', $campaignEmail->slug);
		$campaignEmail->enabled      = (bool) craft()->request->getPost('enabled', $campaignEmail->enabled);
		$campaignEmail->fromName     = craft()->request->getPost('sproutEmail.fromName');
		$campaignEmail->fromEmail    = craft()->request->getPost('sproutEmail.fromEmail');
		$campaignEmail->replyToEmail = craft()->request->getPost('sproutEmail.replyToEmail');
		$campaignEmail->subjectLine  = craft()->request->getRequiredPost('subjectLine');

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
