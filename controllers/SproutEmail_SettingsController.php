<?php
namespace Craft;

class SproutEmail_SettingsController extends BaseController
{
	/**
	 * Save Settings to the Database
	 *
	 * @return mixed Return to Page
	 */
	public function actionSettingsIndexTemplate()
	{
		$settingsModel = new SproutEmail_SettingsModel;

		$settings = craft()->db->createCommand()
			->select('settings')
			->from('plugins')
			->where('class=:class', array(':class'=> 'SproutEmail'))
			->queryScalar();

		$settings = JsonHelper::decode($settings);
		$settingsModel->setAttributes($settings);

		$variables['settings'] = $settingsModel;

		// Load our template
		$this->renderTemplate('sproutemail/settings', $variables);

	}

	/**
	 * Save Plugin Settings
	 * 
	 * @return void
	 */
	public function actionSaveSettings()
	{
		$this->requirePostRequest();
		$settings = craft()->request->getPost('settings');

		if (craft()->sproutEmail_settings->saveSettings($settings))
		{
			craft()->userSession->setNotice(Craft::t('Settings saved.'));

			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save settings.'));

			// Send the settings back to the template
			craft()->urlManager->setRouteVariables(array(
				'settings' => $settings
			));
		}
	}
}
