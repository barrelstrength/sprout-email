<?php
namespace Craft;

class SproutEmail_MailerController extends BaseController
{
	/**
	 * Allows Sprout Email to officially register mailers already installed via Craft
	 *
	 * @note This is called onAfterInstall() and on sproutemail/settings/mailers [Refresh List]
	 */
	public function actionInstallMailers()
	{
		sproutEmail()->mailers->installMailers();

		craft()->userSession->setNotice(Craft::t('Mailers refreshed successfully.'));

		$this->redirect(UrlHelper::getCpUrl('sproutemail/settings/mailers'));
	}

	/**
	 * @param array $variables
	 *
	 * @throws HttpException
	 */
	public function actionEditSettings(array $variables = array())
	{
		if (!isset($variables['mailerId']))
		{
			throw new HttpException(404, Craft::t('No mailer id was provided'));
		}

		$mailer = sproutEmail()->mailers->getMailerByName($variables['mailerId']);

		if (!$mailer)
		{
			throw new HttpException(404, Craft::t('No mailer was found with that id'));
		}

		if (!$mailer->hasCpSettings())
		{
			throw new HttpException(404, Craft::t('No settings found for this mailer'));
		}

		$context = array(
			'mailer'   => $mailer,
			'settings' => $mailer->getSettings()
		);

		$this->renderTemplate('sproutemail/settings/_mailer', $context);
	}

	/**
	 * Provides a consistent way of validating and saving settings across mailers
	 *
	 * @throws HttpException
	 */
	public function actionSaveSettings()
	{
		$this->requirePostRequest();

		$model  = null;
		$mailer = sproutEmail()->mailers->getMailerByName(craft()->request->getRequiredPost('mailerId'));

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

		craft()->userSession->setError(Craft::t('Settings could not be saved.'));
		craft()->urlManager->setRouteVariables(array('settings' => $model));
	}

	/**
	 * Provides a way for mailers to render content to perform actions inside a our modal window
	 *
	 * @throws HttpException
	 */
	public function actionGetPrepareModal()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$mailer     = craft()->request->getRequiredPost('mailer');
		$entryId    = craft()->request->getRequiredPost('entryId');
		$campaignId = craft()->request->getRequiredPost('campaignId');

		$modal = sproutEmail()->mailers->getPrepareModal($mailer, $entryId, $campaignId);

		$this->returnJson($modal->getAttributes());
	}

	/**
	 * Provides a way for mailers to render content to perform actions inside a modal window
	 *
	 * @throws HttpException
	 */
	public function actionGetPreviewModal()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$mailer     = craft()->request->getRequiredPost('mailer');
		$entryId    = craft()->request->getRequiredPost('entryId');
		$campaignId = craft()->request->getRequiredPost('campaignId');

		$modal = sproutEmail()->mailers->getPreviewModal($mailer, $entryId, $campaignId);

		$this->returnJson($modal->getAttributes());
	}
}
