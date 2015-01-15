<?php
namespace Craft;

class SproutEmail_EntryController extends BaseController
{
	/**
	 * The campaign that this entry is associated with if any
	 *
	 * @var SproutEmail_Campaign
	 */
	private $campaign;

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

		$campaignId     = craft()->request->getRequiredPost('campaignId');
		$this->campaign = sproutEmail()->campaigns->getCampaignById($campaignId);

		if (!$this->campaign)
		{
			throw new Exception(Craft::t('No Campaign exists with the id “{id}”', array('id' => $campaignId)));
		}

		$entry = $this->getEntryModel();
		$entry = $this->populateEntryModel($entry);

		// Only use the Title Format if it exists
		// @TODO - hide Title Format by default, only show it if
		// Has auto-generated Title field is checked
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
		$entry   = sproutEmail()->entries->getEntryById($entryId);

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
	 * Fetch or create a SproutEmail_EntryModel
	 *
	 * @throws Exception
	 * @return SproutEmail_EntryModel
	 */
	protected function getEntryModel()
	{
		$entryId = craft()->request->getPost('entryId');

		if ($entryId)
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
	 * Populate a SproutEmail_EntryModel with post data
	 *
	 * @param SproutEmail_EntryModel $entry
	 *
	 * @return \Craft\SproutEmail_EntryModel
	 */
	protected function populateEntryModel(SproutEmail_EntryModel $entry)
	{
		$entry->campaignId  = $this->campaign->id;
		$entry->slug        = craft()->request->getPost('slug', $entry->slug);
		$entry->enabled     = (bool) craft()->request->getPost('enabled', $entry->enabled);
		$entry->fromName    = craft()->request->getRequiredPost('sproutEmail.fromName');
		$entry->fromEmail   = craft()->request->getRequiredPost('sproutEmail.fromEmail');
		$entry->replyTo     = craft()->request->getRequiredPost('sproutEmail.replyTo');
		$entry->subjectLine = craft()->request->getRequiredPost('subjectLine');

		$entry->getContent()->title = $entry->subjectLine;

		if (empty($entry->slug))
		{
			$entry->slug = ElementHelper::createSlug($this->subjectLine);
		}

		$fieldsLocation = craft()->request->getParam('fieldsLocation', 'fields');

		$entry->setContentFromPost($fieldsLocation);
		$entry->setContentPostLocation($fieldsLocation);

		return $entry;
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
		$entryId    = craft()->request->getSegment(4);
		$campaignId = craft()->request->getSegment(3);

		// Check if we already have an entry route variable
		// If so it's probably due to a bad form submission and has an error object
		// that we don't want to overwrite.
		if (!isset($variables['entry']))
		{
			if (is_numeric($entryId))
			{
				$variables['entry']   = craft()->elements->getElementById($entryId);
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

		$variables['campaign']   = sproutEmail()->campaigns->getCampaignById($campaignId);
		$variables['campaignId'] = $campaignId;

		// Tabs
		$variables['tabs'] = array();

		foreach ($variables['campaign']->getFieldLayout()->getTabs() as $index => $tab)
		{
			// Do any of the fields on this tab have errors?
			$hasErrors = false;

			if ($variables['entry']->hasErrors())
			{
				foreach ($tab->getFields() as $field)
				{
					if ($variables['entry']->getErrors($field->getField()->handle))
					{
						$hasErrors = true;
						break;
					}
				}
			}

			$variables['tabs'][] = array(
				'label' => Craft::t($tab->name),
				'url'   => '#tab'.($index + 1),
				'class' => ($hasErrors ? 'error' : null)
			);
		}

		// @todo Figure out whay this was in a conditional and what the condition should be
		$shareParamsHtml = array(
			'entryId'  => $variables['entry']->id,
			'template' => 'html'
		);

		$shareParamsText = array(
			'entryId'  => $variables['entry']->id,
			'template' => 'txt'
		);

		if ($variables['campaign']->type == 'notification')
		{
			$notification = sproutEmail()->notifications->getNotification(array('campaignId' => $campaignId));
			$variables['notificationEvent'] = $notification->eventId;
		}

		$variables['shareUrlHtml']      = UrlHelper::getActionUrl('sproutEmail/entry/shareEntry', $shareParamsHtml);
		$variables['shareUrlText']      = UrlHelper::getActionUrl('sproutEmail/entry/shareEntry', $shareParamsText);
		// end <

		$variables['recipientLists']		= sproutEmail()->entries->getRecipientListsByEntryId($variables['entryId']);

		$this->renderTemplate('sproutemail/entries/_edit', $variables);
	}

	/**
	 * Redirects the client to a URL for viewing an entry/draft on the front end.
	 *
	 * @param mixed  $entryId
	 * @param string $template
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function actionShareEntry($entryId = null, $template = null)
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
				'template' => $template,
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
		$url   = UrlHelper::getUrlWithToken($entry->getUrl($template), $token);

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

		$this->_showEntry($entry, $template);
	}

	/**
	 * @param SproutEmail_EntryModel $entry
	 *
	 * @throws HttpException
	 */
	private function _showEntry(SproutEmail_EntryModel $entry, $template = null)
	{
		// @TODO
		// Grab Campaign
		// Make sure it exists
		// Get the template value from the Campaign settings
		// ------------------------------------------------------------

		$campaign = $entry->getType();

		if ($campaign)
		{
			craft()->templates->getTwig()->disableStrictVariables();

			craft()->path->setTemplatesPath(craft()->path->getSiteTemplatesPath());

			$this->renderTemplate(
				$campaign->template.'.'.$template, array(
					'entry' => $entry
				)
			);
		}
		else
		{
			Craft::log('Attempting to preview an Entry that does not exist', LogLevel::Error);
			throw new HttpException(404);
		}
	}

	/**
	 * @param SproutEmail_CampaignModel $campaign
	 *
	 * @throws Exception
	 */
	protected function saveNotificationRules($campaign)
	{
		$notificationEvent = craft()->request->getPost('sproutEmail.notificationEvent');

		if ($notificationEvent)
		{
			$events = sproutEmail()->notifications->getAvailableEvents();

			if (!isset($events[$notificationEvent]))
			{
				throw new Exception(Craft::t('The {e} is not available for subscription.', array('e' => $notificationEvent)));
			}

			sproutEmail()->notifications->save($events[$notificationEvent], $campaign->id, craft()->request->getPost());
		}
	}
}
