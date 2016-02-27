<?php
namespace Craft;

/**
 * Class SproutEmail_EntryController
 *
 * @package Craft
 */
class SproutEmail_EntryController extends BaseController
{
	/**
	 * List of actions allowed to be called from outside the Control Panel
	 *
	 * @var array
	 */
	protected $allowAnonymous = array('actionViewSharedEntry');

	/**
	 * The campaign that this entry is associated with if any
	 *
	 * @var SproutEmail_CampaignModel
	 */
	protected $campaign;

	/**
	 * Saves a campaign entry
	 *
	 * @throws Exception
	 * @throws HttpException
	 * @return void
	 */
	public function actionSaveEntry()
	{
		$this->requirePostRequest();

		$campaignId = craft()->request->getRequiredPost('campaignId');
		$this->campaign = sproutEmail()->campaigns->getCampaignById($campaignId);

		if (!$this->campaign)
		{
			throw new Exception(Craft::t('No Campaign exists with the id “{id}”', array('id' => $campaignId)));
		}

		$entry = $this->getEntryModel();
		$entry = $this->populateEntryModel($entry);

		if (craft()->request->getPost('saveAsNew'))
		{
			$entry->saveAsNew = true;
			$entry->id = null;
		}

		if ($this->campaign->titleFormat)
		{
			$entry->getContent()->title = craft()->templates->renderObjectTemplate($this->campaign->titleFormat, $entry);
		}

		if (sproutEmail()->entries->saveEntry($entry, $this->campaign))
		{
			craft()->userSession->setNotice(Craft::t('Entry saved.'));

			$_POST['redirect'] = str_replace('{id}', $entry->id, $_POST['redirect']);

			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Could not save Entry.'));

			craft()->urlManager->setRouteVariables(
				array(
					'entry' => $entry
				)
			);
		}
	}

	/**
	 * Delete an Entry
	 *
	 * @return void
	 */
	public function actionDeleteEntry()
	{
		$this->requirePostRequest();

		// Get the Entry
		$entryId = craft()->request->getRequiredPost('entryId');
		$entry = sproutEmail()->entries->getEntryById($entryId);

		if (sproutEmail()->entries->deleteEntry($entry))
		{
			$this->redirectToPostedUrl($entry);
		}
		else
		{
			// @TODO - return errors
			SproutEmailPlugin::log(json_encode($entry->getErrors()));
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
				$response['message'] = Craft::t('You did not return any content from {method}().', array('method' => $method));

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

		$entry = sproutEmail()->entries->getEntryById(craft()->request->getPost('entryId'));

		if ($entry && ($campaign = sproutEmail()->campaigns->getCampaignById($entry->campaignId)))
		{
			try
			{
				$response = sproutEmail()->mailers->exportEntry($entry, $campaign);

				if ($response instanceof SproutEmail_ResponseModel)
				{
					if ($response->success == true)
					{
						if ($response->emailModel != null)
						{
							$emailModel = $response->emailModel;

							$event = new Event($this, array(
								'entryModel' => $entry,
								'emailModel' => $emailModel,
								'campaign'   => $campaign
							));

							sproutEmail()->onSendCampaign($event);
						}
					}

					$this->returnJson($response);
				}

				$errorMessage = Craft::t('Mailer did not return a valid response model after entry export.');

				if (!$response)
				{
					$errorMessage = Craft::t('Unable to send email.');
				}

				$this->returnJson(
					SproutEmail_ResponseModel::createErrorModalResponse(
						'sproutemail/_modals/export',
						array(
							'entry'    => $entry,
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
							'entry'    => $entry,
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
					'entry'    => $entry,
					'campaign' => !empty($campaign) ? $campaign : null,
					'message'  => Craft::t('The campaign email you are trying to send is missing.'),
				)
			)
		);
	}

	/**
	 * Route Controller for Edit Entry Template
	 *
	 * @param array $variables
	 *
	 * @throws HttpException
	 * @throws Exception
	 */
	public function actionEditEntryTemplate(array $variables = array())
	{
		$entryId = craft()->request->getSegment(4);
		$campaignId = craft()->request->getSegment(3);

		// Check if we already have an entry route variable
		// If so it's probably due to a bad form submission and has an error object
		// that we don't want to overwrite.
		if (!isset($variables['entry']))
		{
			if (is_numeric($entryId))
			{
				$variables['entry'] = craft()->elements->getElementById($entryId);
				$variables['entryId'] = $entryId;
			}
			else
			{
				$variables['entry'] = new SproutEmail_EntryModel();
			}
		}

		if (!is_numeric($campaignId))
		{
			$campaignId = $variables['entry']->campaignId;
		}

		$variables['campaign'] = sproutEmail()->campaigns->getCampaignById($campaignId);
		$variables['campaignId'] = $campaignId;

		$shareParamsHtml = array(
			'entryId'  => $variables['entry']->id,
			'template' => 'html'
		);

		$shareParamsText = array(
			'entryId'  => $variables['entry']->id,
			'template' => 'txt'
		);

		// Enable Live Preview?
		if (!craft()->request->isMobileBrowser(true) && sproutEmail()->doesSiteTemplateExist($variables['campaign']->template))
		{
			craft()->templates->includeJs(
				'Craft.LivePreview.init(' . JsonHelper::encode(
					array(
						'fields'        => '#subjectLine-field, #title-field, #fields > div > div > .field',
						'extraFields'   => '#settings',
						'previewUrl'    => $variables['entry']->getUrl(),
						'previewAction' => 'sproutEmail/entry/livePreviewEntry',
						'previewParams' => array(
							'entryId'    => $variables['entry']->id,
							'campaignId' => $variables['campaign']->id,
						)
					)
				) . ');'
			);

			$variables['showPreviewBtn'] = true;

			// Should we show the Share button too?
			if ($variables['entry']->id)
			{
				if ($variables['entry']->enabled)
				{
					$variables['shareUrl'] = $variables['entry']->getUrl();
				}
				else
				{
					$shareParams = array(
						'entryId'    => $variables['entry']->id,
						'campaignId' => $variables['campaign']->id
					);

					$variables['shareUrl'] = UrlHelper::getActionUrl('sproutEmail/entry/shareEntry', $shareParams);
				}
			}
		}
		else
		{
			$variables['showPreviewBtn'] = false;
		}

		if ($variables['campaign']->type == 'notification')
		{
			$notificationId = null;
			$notification = sproutEmail()->notifications->getNotification(array('campaignId' => $campaignId));

			if ($notification)
			{
				$notificationId = $notification->eventId;
			}

			$variables['notificationEvent'] = $notificationId;
		}

		// end <

		$variables['recipientLists'] = sproutEmail()->entries->getRecipientListsByEntryId($entryId);

		$this->renderTemplate('sproutemail/entries/_edit', $variables);
	}

	public function actionPreview()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$entry = sproutEmail()->entries->getEntryById(craft()->request->getPost('entryId'));

		if ($entry && ($campaign = sproutEmail()->campaigns->getCampaignById($entry->campaignId)))
		{
			try
			{
				$result = sproutEmail()->mailers->previewEntry($entry, $campaign);

				if (craft()->request->isAjaxRequest())
				{
					return $result['content'];
					// $this->returnJson($result);
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
			throw new Exception(Craft::t('Entry or Campaign is missing'));
		}
	}

	/**
	 * @throws HttpException
	 */
	public function actionLivePreviewEntry()
	{
		$entryId = craft()->request->getPost('entryId');

		if ($entryId)
		{
			$entry = sproutEmail()->entries->getEntryById($entryId);

			if (!$entry)
			{
				throw new HttpException(404);
			}
		}
		else
		{
			$entry = new SproutEmail_EntryModel();
		}

		$entry->subjectLine = craft()->request->getPost('subjectLine', $entry->subjectLine);
		$entry->getContent()->title = $entry->subjectLine;

		$fieldsLocation = craft()->request->getParam('fieldsLocation', 'fields');

		$entry->setContentFromPost($fieldsLocation);

		$this->showEntry($entry);
	}

	/**
	 * Redirects the client to a URL for viewing an entry/draft on the front end.
	 *
	 * @param mixed $entryId
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function actionShareEntry($entryId = null)
	{
		if ($entryId)
		{
			$entry = sproutEmail()->entries->getEntryById($entryId);

			if (!$entry)
			{
				throw new HttpException(404);
			}

			$params = array(
				'entryId' => $entryId,
			);
		}
		else
		{
			throw new HttpException(404);
		}

		// Create the token and redirect to the entry URL with the token in place
		$token = craft()->tokens->createToken(
			array(
				'action' => 'sproutEmail/entry/viewSharedEntry',
				'params' => $params
			)
		);

		$url = UrlHelper::getUrlWithToken($entry->getUrl(), $token);

		craft()->request->redirect($url);
	}

	/**
	 * @param null $entryId
	 *
	 * @throws HttpException
	 */
	public function actionViewSharedEntry($entryId = null, $template = null)
	{
		$this->requireToken();

		if ($entryId)
		{
			$entry = sproutEmail()->entries->getEntryById($entryId);
		}

		if (!$entry)
		{
			throw new HttpException(404);
		}

		$this->showEntry($entry, $template);
	}

	/**
	 * @param SproutEmail_EntryModel $entry
	 *
	 * @throws HttpException
	 */
	protected function showEntry(SproutEmail_EntryModel $entry, $template = null)
	{
		// @TODO
		// Grab Campaign
		// Make sure it exists
		// Get the template value from the Campaign settings
		// ------------------------------------------------------------

		$campaign = sproutEmail()->campaigns->getCampaignById($entry->campaignId);

		if ($campaign)
		{
			craft()->templates->getTwig()->disableStrictVariables();

			craft()->path->setTemplatesPath(craft()->path->getSiteTemplatesPath());

			$ext = '';

			if (in_array($template, array('txt', 'text')))
			{
				$ext = '.txt';
			}

			$this->renderTemplate(
				$campaign->template . $ext, array(
					'entry'     => $entry,
					'campaign'  => $campaign,
					'firstName' => '{firstName}',
					'lastName'  => '{lastName}',
					'email'     => '{email}',
				)
			);
		}
		else
		{
			SproutEmailPlugin::log('Attempting to preview an Entry that does not exist', LogLevel::Error);

			throw new HttpException(404);
		}
	}

	/**
	 * Fetch or create a SproutEmail_EntryModel
	 *
	 * @throws Exception
	 * @return SproutEmail_EntryModel
	 */
	protected function getEntryModel()
	{
		$entryId = craft()->request->getPost('entryId');
		$saveAsNew = craft()->request->getPost('saveAsNew');

		if ($entryId && !$saveAsNew)
		{
			$entry = sproutEmail()->entries->getEntryById($entryId);

			if (!$entry)
			{
				throw new Exception(Craft::t('No entry exists with the ID “{id}”', array('id' => $entryId)));
			}
		}
		else
		{
			$entry = new SproutEmail_EntryModel();
		}

		return $entry;
	}

	/**
	 * Populates a SproutEmail_EntryModel with post data
	 *
	 * @param SproutEmail_EntryModel $entry
	 *
	 * @return \Craft\SproutEmail_EntryModel
	 */
	protected function populateEntryModel(SproutEmail_EntryModel $entry)
	{
		$entry->campaignId = $this->campaign->id;
		$entry->slug = craft()->request->getPost('slug', $entry->slug);
		$entry->enabled = (bool) craft()->request->getPost('enabled', $entry->enabled);
		$entry->fromName = craft()->request->getPost('sproutEmail.fromName');
		$entry->fromEmail = craft()->request->getPost('sproutEmail.fromEmail');
		$entry->replyTo = craft()->request->getPost('sproutEmail.replyTo');
		$entry->subjectLine = craft()->request->getRequiredPost('subjectLine');

		$enableFileAttachments = craft()->request->getPost('sproutEmail.enableFileAttachments');
		$entry->enableFileAttachments = $enableFileAttachments ? $enableFileAttachments : false;

		$entry->getContent()->title = $entry->subjectLine;

		if (empty($entry->slug))
		{
			$entry->slug = ElementHelper::createSlug($entry->subjectLine);
		}

		$fieldsLocation = craft()->request->getParam('fieldsLocation', 'fields');

		$entry->setContentFromPost($fieldsLocation);
		$entry->setContentPostLocation($fieldsLocation);

		return $entry;
	}
}
