<?php
namespace Craft;

class SproutEmail_MailerController extends BaseController
{
	public function actionInstallMailers()
	{
		sproutEmail()->mailers->installMailers();

		craft()->userSession->setNotice(Craft::t('Mailers refreshed successfully.'));

		$this->redirect(UrlHelper::getCpUrl('sproutemail/settings?tab=mailers'));
	}

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
}
