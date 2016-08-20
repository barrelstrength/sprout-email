<?php
namespace Craft;

class SproutEmail_MailerController extends BaseController
{
	/**
	 * Renders the Mailer Edit template
	 *
	 * @param array $variables
	 *
	 * @throws HttpException
	 */
	public function actionEditSettingsTemplate(array $variables = array())
	{
		$mailerId = isset($variables['mailerId']) ? $variables['mailerId'] : null;
		$settings = isset($variables['settings']) ? $variables['settings'] : null;

		if (!$mailerId)
		{
			throw new HttpException(404, Craft::t('No mailer id was provided'));
		}

		$mailer = sproutEmail()->mailers->getMailerByName($mailerId);

		if (!$mailer)
		{
			throw new HttpException(404, Craft::t('No mailer was found with that id'));
		}

		if (!$mailer->hasCpSettings())
		{
			throw new HttpException(404, Craft::t('No settings found for this mailer'));
		}

		if (!$settings)
		{
			$settings = $mailer->getSettings();
		}

		$this->renderTemplate('sproutemail/settings/mailers/edit', array(
			'mailer'   => $mailer,
			'settings' => $settings
		));
	}

	/**
	 * Validate and save settings across mailers
	 *
	 * @throws HttpException
	 */
	public function actionSaveSettings()
	{
		$this->requirePostRequest();

		$model = null;

		$mailerId = craft()->request->getRequiredPost('mailerId');
		$mailer   = sproutEmail()->mailers->getMailerByName($mailerId);

		if ($mailer)
		{
			$record = sproutEmail()->mailers->getMailerRecordByName($mailer->getId());

			if ($record)
			{
				$model = sproutEmail()->mailers->getSettingsByMailerName($mailer->getId());

				$model->setAttributes($mailer->prepareSettings());

				if ($model->validate())
				{
					$record->setAttribute('settings', $model->getAttributes());

					if ($record->save(false))
					{
						craft()->userSession->setNotice(Craft::t('Settings successfully saved.'));

						$this->redirectToPostedUrl($model);
					}
				}
			}
		}

		craft()->userSession->setError(Craft::t('Unable to save settings.'));

		craft()->urlManager->setRouteVariables(array(
			'settings' => $model
		));
	}

	/**
	 * Provides a way for mailers to render content to perform actions inside a a modal window
	 *
	 * @throws HttpException
	 */
	public function actionGetPrepareModal()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$mailer         = craft()->request->getRequiredPost('mailer');
		$emailId        = craft()->request->getRequiredPost('emailId');
		$campaignTypeId = craft()->request->getRequiredPost('campaignTypeId');

		$modal = sproutEmail()->mailers->getPrepareModal($mailer, $emailId, $campaignTypeId);

		$this->returnJson($modal->getAttributes());
	}

	/**
	 * Allows Sprout Email to officially register mailers already installed via Craft
	 *
	 * @note This is called onAfterInstall() and on sproutemail/settings/mailers [Refresh List]
	 */
	public function actionInstallMailers()
	{
		sproutEmail()->mailers->installMailers();

		craft()->userSession->setNotice(Craft::t('Mailers refreshed successfully.'));

		$url = UrlHelper::getCpUrl('sproutemail/settings/mailers');

		$this->redirect($url);
	}
}
