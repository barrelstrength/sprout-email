<?php

namespace Craft;

class SproutEmail_CopyPasteController extends BaseController
{
	public function actionMarkSent()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$emailId = craft()->request->getRequiredPost('emailId');

		$campaignEmail = sproutEmail()->campaignEmails->getCampaignEmailById($emailId);
		$campaignType  = sproutEmail()->campaignTypes->getCampaignTypeById($campaignEmail->campaignTypeId);

		$campaignEmail->lastDateSent = DateTimeHelper::currentTimeForDb();

		if (sproutEmail()->campaignEmails->saveCampaignEmail($campaignEmail, $campaignType))
		{
			$html = craft()->templates->render('sproutemail/_modals/sendEmailConfirmation', array(
				'success' => true,
				'email'   => $campaignEmail,
				'message' => Craft::t('Email marked as sent.')
			));

			$this->returnJson(array(
				'success' => true,
				'content' => $html
			));
		}
		else
		{
			$html = craft()->templates->render('sproutemail/_modals/sendEmailConfirmation', array(
				'success' => true,
				'email'   => $campaignEmail,
				'message' => Craft::t('Unable to mark email as sent.')
			));

			$this->returnJson(array(
				'success' => true,
				'content' => $html
			));
		}
	}
}
