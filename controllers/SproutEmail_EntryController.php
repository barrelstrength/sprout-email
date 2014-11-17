<?php
namespace Craft;

class SproutEmail_EntryController extends BaseController
{
	private $campaign;
	/**
	 * Save Entry
	 * 
	 * @return void
	 */
	public function actionSaveEntry()
	{		
		$this->requirePostRequest();

		$campaignId = craft()->request->getRequiredPost('campaignId');
		$this->campaign = craft()->sproutEmail_campaign->getCampaignById($campaignId);

		if (!isset($this->campaign)) 
		{
			throw new Exception(Craft::t('No Campaign exists with the id “{id}”', array('id' => $campaignId)));
		}

		$entry = $this->_getEntryModel();
		
		// Populate the entry with post data
		// @TODO - This function doesn't update our $entry variable, why?
		$this->_populateEntryModel($entry);

		// Only use the Title Format if it exists
		// @TODO - hide Title Format by default, only show it if 
		// Has auto-generated Title field is checked
		if ($this->campaign->titleFormat) 
		{
			$entry->getContent()->title = craft()->templates->renderObjectTemplate($this->campaign->titleFormat, $entry);
		}

		if (craft()->sproutEmail_entry->saveEntry($entry)) 
		{	
			craft()->userSession->setNotice(Craft::t('Entry saved.'));
			
			$_POST['redirect'] = str_replace('{id}', $entry->id, $_POST['redirect']);

			$this->redirectToPostedUrl();
		}
		else
		{	
			// make errors available to variable
			craft()->userSession->setError(Craft::t('Couldn’t save Entry.'));

			// Return the form as an 'entry' variable if in the cp
			craft()->urlManager->setRouteVariables(array(
				'entry' => $entry
			));
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
		$entry = craft()->sproutEmail_entry->getEntryById($entryId);
		
		if (craft()->sproutEmail_entry->deleteEntry($entry))
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
	 * @access private
	 * @throws Exception
	 * @return SproutEmail_EntryModel
	 */
	private function _getEntryModel()
	{
		$entryId = craft()->request->getPost('entryId');

		if ($entryId)
		{
			$entry = craft()->sproutEmail_entry->getEntryById($entryId);

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
	 * @access private
	 * @param SproutEmail_EntryModel $entry
	 */
	private function _populateEntryModel(SproutEmail_EntryModel $entry)
	{
		$entry->slug = craft()->request->getPost('slug', $entry->slug);
		$entry->enabled = (bool) craft()->request->getPost('enabled', $entry->enabled);

		$entry->getContent()->title = craft()->request->getRequiredPost('subjectLine');
		$entry->subjectLine = $entry->getContent()->title;
		$entry->campaignId = $this->campaign->id;

		// Set the entry attributes, defaulting to the existing values for whatever is missing from the post data
		
		$fieldsLocation = craft()->request->getParam('fieldsLocation', 'fields');
		$entry->setContentFromPost($fieldsLocation);
		$entry->setContentPostLocation($fieldsLocation);
	}

	/**
	 * Route Controller for Edit Entry Template
	 *
	 * @param array $variables
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
		if ( ! isset($variables['entry']) ) 
		{
			if (is_numeric($entryId)) 
			{
				$variables['entry'] = craft()->elements->getElementById($entryId);
				$variables['entryId'] = $entryId;
			}	
			else
			{
				$variables['entry'] = new SproutEmail_EntryModel();

				// @TODO - fix error
				$variables['entryId'] = "";
			}
		}
		else
		{
			$variables['entryId'] = "";
		}
		
		if (!is_numeric($campaignId)) 
		{
			$campaignId = $variables['entry']->campaignId;
		}

		$variables['campaign'] = craft()->sproutEmail_campaign->getCampaignById($campaignId);
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
				'url'   => '#tab'.($index+1),
				'class' => ($hasErrors ? 'error' : null)
			);
		}

		// Share Email (if not Live)
		if (false) 
		{
			//$variables['shareUrl'] = $variables['entry']->url;
		}
		else
		{
			$shareParamsHtml = array(
				'entryId' => $variables['entry']->id,
				'template' => 'html'
			);

			$shareParamsText = array(
				'entryId' => $variables['entry']->id,
				'template' => 'text'
			);

			$variables['shareUrlHtml'] = UrlHelper::getActionUrl('sproutEmail/entry/shareEntry', $shareParamsHtml);
			$variables['shareUrlText'] = UrlHelper::getActionUrl('sproutEmail/entry/shareEntry', $shareParamsText);		
		}
		

		$this->renderTemplate('sproutemail/entries/_edit', $variables);
	}


	/**
	 * Redirects the client to a URL for viewing an entry/draft/version on the front end.
	 *
	 * @param mixed $entryId
	 * @param mixed $locale
	 * @param mixed $draftId
	 * @param mixed $versionId
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function actionShareEntry($entryId = null, $template = null)
	{
		if ($entryId)
		{
			$entry = craft()->sproutEmail_entry->getEntryById($entryId);

			if (!$entry)
			{
				throw new HttpException(404);
			}

			$params = array(
				'entryId' => $entryId
			);
		}
		else
		{
			throw new HttpException(404);
		}

		// Make sure they have permission to be viewing this entry
		// $this->enforceEditEntryPermissions($entry);

		// Make sure the entry actually can be viewed
		// if (!craft()->sections->isSectionTemplateValid($entry->getSection()))
		// {
		// 	throw new HttpException(404);
		// }

		// Create the token and redirect to the entry URL with the token in place
		$token = craft()->tokens->createToken(array(
			'action' => 'sproutEmail/entry/viewSharedEntry', 
			'params' => $params
		));
		$url = UrlHelper::getUrlWithToken($entry->getUrl($template), $token);
		craft()->request->redirect($url);
	}

	/**
	 * Shows an entry/draft/version based on a token.
	 *
	 * @param mixed $entryId
	 * @param mixed $locale
	 * @param mixed $draftId
	 * @param mixed $versionId
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function actionViewSharedEntry($entryId = null)
	{
		$this->requireToken();

		if ($entryId)
		{
			$entry = craft()->sproutEmail_entry->getEntryById($entryId);
		}

		if (!$entry)
		{
			throw new HttpException(404);
		}

		$this->_showEntry($entry);
	}

	/**
	 * Displays an entry.
	 *
	 * @param EntryModel $entry
	 *
	 * @throws HttpException
	 * @return null
	 */
	private function _showEntry(SproutEmail_EntryModel $entry)
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

			$this->renderTemplate($campaign->template, array(
				'entry' => $entry
			));
		}
		else
		{
			Craft::log('Attempting to preview an Entry that does not exist', LogLevel::Error);
			throw new HttpException(404);
		}
	}
}