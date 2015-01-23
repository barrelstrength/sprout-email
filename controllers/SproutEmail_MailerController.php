<?php
namespace Craft;

class SproutEmail_MailerController extends BaseController
{
	/**
	 * Allows Sprout Email to officially register mailers already installed via Craft
	 *
	 * @note This is called onAfterInstall() and on sproutemail/settings/?tab=mailers [Refresh List]
	 */
	public function actionInstallMailers()
	{
		sproutEmail()->mailers->installMailers();

		craft()->userSession->setNotice(Craft::t('Mailers refreshed successfully.'));

		$this->redirect(UrlHelper::getCpUrl('sproutemail/settings?tab=mailers'));
	}

	/**
	 * Provides a consistent way of validating and saving settings across mailers
	 *
	 * @throws HttpException
	 */
	public function actionSaveSettings()
	{
		$this->requirePostRequest();

		$models  = array();
		$success = true;
		$mailers = sproutEmail()->mailers->getMailers();

		foreach ($mailers as $mailer)
		{
			$record = sproutEmail()->mailers->getMailerRecordByName($mailer->getId());

			if ($record)
			{
				$model = sproutEmail()->mailers->getSettingsByMailerName($mailer->getId());

				$model->setAttributes($mailer->prepareSettings());

				$canSave = $model->validate();

				$models[$mailer->getId()] = $model;

				if ($canSave)
				{
					$record->setAttribute('settings', $model->getAttributes());
					$record->save();
				}
				else
				{
					$success = false;
				}
			}
		}

		if ($success)
		{
			craft()->userSession->setNotice(Craft::t('Settings successfully saved.'));
			$this->redirectToPostedUrl();
		}

		craft()->userSession->setError(Craft::t('Settings could not be saved.'));
		craft()->userSession->setFlash('models', $models);
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
