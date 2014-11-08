<?php
namespace Craft;

class SproutEmail_EmailBlastController extends BaseController
{
	private $emailBlastType;
	/**
	 * Save Email Blast
	 * 
	 * @return void
	 */
	public function actionSaveEmailBlast()
	{		
		$this->requirePostRequest();

		$emailBlastTypeId = craft()->request->getRequiredPost('emailBlastTypeId');
		$this->emailBlastType = craft()->sproutEmail->getEmailBlastTypeById($emailBlastTypeId);

		if (!isset($this->emailBlastType)) 
		{
			throw new Exception(Craft::t('No Email Blast Type exists with the id “{id}”', array('id' => $emailBlastTypeId)));
		}

		$emailBlast = $this->_getEmailBlastModel();
		
		// Populate the entry with post data
		// @TODO - This function doesn't update our $entry variable, why?
		$this->_populateEmailBlastModel($emailBlast);

		// Only use the Title Format if it exists
		// @TODO - hide Title Format by default, only show it if 
		// Has auto-generated Title field is checked
		if ($this->emailBlastType->titleFormat) 
		{
			$emailBlast->getContent()->title = craft()->templates->renderObjectTemplate($this->emailBlastType->titleFormat, $emailBlast);
		}

		if (craft()->sproutEmail_emailBlast->saveEmailBlast($emailBlast)) 
		{	
			craft()->userSession->setNotice(Craft::t('Email blast saved.'));
			
			$_POST['redirect'] = str_replace('{id}', $emailBlast->id, $_POST['redirect']);

			$this->redirectToPostedUrl();
		}
		else
		{	
			// make errors available to variable
			craft()->userSession->setError(Craft::t('Couldn’t save email blast.'));

			// Return the form as an 'emailBlast' variable if in the cp
			craft()->urlManager->setRouteVariables(array(
				'emailBlast' => $emailBlast
			));
		}
	}

	/**
	 * Delete an entry.
	 * 
	 * @return void
	 */
	public function actionDeleteEntry()
	{	
		$this->requirePostRequest();
		
		// Get the Entry
		$entryId = craft()->request->getRequiredPost('entryId');
		$entry = craft()->sproutForms_entries->getEntryById($entryId);
		
		// @TODO - handle errors
		$success = craft()->sproutForms_entries->deleteEntry($entry);

		$this->redirectToPostedUrl($entry);
	}

	/**
	 * Fetch or create a SproutEmail_EmailBlastModel
	 *
	 * @access private
	 * @throws Exception
	 * @return SproutEmail_EmailBlastModel
	 */
	private function _getEmailBlastModel()
	{
		$emailBlastId = craft()->request->getPost('emailBlastId');

		if ($emailBlastId)
		{
			$emailBlast = craft()->sproutEmail_emailBlast->getEmailBlastById($emailBlastId);

			if (!$emailBlast)
			{
				throw new Exception(Craft::t('No entry exists with the ID “{id}”', array('id' => $emailBlastId)));
			}
		}
		else
		{
			$emailBlast = new SproutEmail_EmailBlastModel();
		}

		return $emailBlast;
	}

	/**
	 * Populate a SproutEmail_EmailBlastModel with post data
	 *
	 * @access private
	 * @param SproutEmail_EmailBlastModel $entry
	 */
	private function _populateEmailBlastModel(SproutEmail_EmailBlastModel $emailBlast)
	{
		$emailBlast->slug = craft()->request->getPost('slug', $emailBlast->slug);
		$emailBlast->enabled = (bool) craft()->request->getPost('enabled', $emailBlast->enabled);

		$emailBlast->getContent()->title = craft()->request->getRequiredPost('subjectLine');
		$emailBlast->subjectLine = $emailBlast->getContent()->title;
		$emailBlast->emailBlastTypeId = $this->emailBlastType->id;

		// Set the entry attributes, defaulting to the existing values for whatever is missing from the post data
		
		$fieldsLocation = craft()->request->getParam('fieldsLocation', 'fields');
		$emailBlast->setContentFromPost($fieldsLocation);
		$emailBlast->setContentPostLocation($fieldsLocation);
	}

	/**
	 * Route Controller for Edit Entry Template
	 *
	 * @param array $variables
	 * @throws HttpException
	 * @throws Exception
	 */
	public function actionEditEmailBlastTemplate(array $variables = array())
	{	
		$emailBlastId = craft()->request->getSegment(4);
		$emailBlastTypeId = craft()->request->getSegment(3);

		// Check if we already have an emailBlast route variable
		// If so it's probably due to a bad form submission and has an error object 
		// that we don't want to overwrite.
		if ( ! isset($variables['emailBlast']) ) 
		{
			if (is_numeric($emailBlastId)) 
			{
				$variables['emailBlast'] = craft()->elements->getElementById($emailBlastId);
				$variables['emailBlastId'] = $emailBlastId;
			}	
			else
			{
				$variables['emailBlast'] = new SproutEmail_EmailBlastModel();

				// @TODO - fix error
				$variables['emailBlastId'] = "";
			}
		}
		else
		{
			$variables['emailBlastId'] = "";
		}
		
		if (!is_numeric($emailBlastTypeId)) 
		{
			$emailBlastTypeId = $variables['emailBlast']->emailBlastTypeId;
		}

		$variables['emailBlastType'] = craft()->sproutEmail->getEmailBlastTypeById($emailBlastTypeId);
		$variables['emailBlastTypeId'] = $emailBlastTypeId;
		
		// Tabs
		$variables['tabs'] = array();

		foreach ($variables['emailBlastType']->getFieldLayout()->getTabs() as $index => $tab) 
		{
			// Do any of the fields on this tab have errors?
			$hasErrors = false;

			if ($variables['emailBlast']->hasErrors())
			{
				foreach ($tab->getFields() as $field)
				{
					if ($variables['emailBlast']->getErrors($field->getField()->handle))
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

		$this->renderTemplate('sproutemail/emailblasts/_edit', $variables);
	}
}