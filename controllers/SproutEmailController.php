<?php

namespace Craft;

class SproutEmailController extends BaseController
{
	/**
	 * Determine which Settings should display on the Settings template
	 *
	 * @throws HttpException
	 */
	public function actionSettingsIndexTemplate()
	{
		$currentUser = craft()->userSession->getUser();

		if (!$currentUser->can('editSproutEmailSettings'))
		{
			$this->redirect('sproutemail');
		}

		if (!craft()->request->getSegment(3))
		{
			$this->redirect('sproutemail/settings/general');
		}

		$settingsTemplate = craft()->request->getSegment(3) . '/index';

		if (craft()->request->getSegment(3) == 'integrations')
		{
			$settingsTemplate = 'integrations/' . craft()->request->getSegment(4);
		}

		$settings = craft()->plugins->getPlugin('sproutemail')->getSettings();

		$this->renderTemplate('sproutemail/settings/' . $settingsTemplate, array(
			'settingsTemplate' => $settingsTemplate,
			'settings'         => $settings
		));
	}

	/**
	 * Save plugin settings
	 *
	 * @return void
	 */
	public function actionSavePluginSettings()
	{
		$this->requirePostRequest();

		$plugin   = craft()->plugins->getPlugin('sproutemail');
		$settings = craft()->request->getPost('settings');

		if (craft()->plugins->savePluginSettings($plugin, $settings))
		{
			craft()->userSession->setNotice(Craft::t('Settings saved.'));

			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Could not save settings.'));

			craft()->urlManager->setRouteVariables(array(
				'settings' => $settings
			));
		}
	}
}
