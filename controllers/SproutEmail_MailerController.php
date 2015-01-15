<?php
namespace Craft;

class SproutEmail_MailerController extends BaseController
{
	public function actionSaveSettings()
	{
		$this->requirePostRequest();

		$mailers = sproutEmail()->mailers->getMailers();

		foreach ($mailers as $mailer)
		{
			$record = sproutEmail()->mailers->getMailerRecordByName($mailer->getId());

			if ($record)
			{
				$record->setAttribute('settings', $mailer->prepareSettings());
				$record->save();
			}
		}

		craft()->userSession->setNotice(Craft::t('Settings successfully saved.'));
		$this->redirectToPostedUrl();
	}
}
