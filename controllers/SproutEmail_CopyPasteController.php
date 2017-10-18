<?php

namespace Craft;

class SproutEmail_CopyPasteController extends BaseController
{
	/**
	 * Updates a Copy/Paste Campaign Email to add a Date Sent
	 */
	public function actionMarkSent()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$emailId = craft()->request->getRequiredPost('emailId');

		$campaignEmail = sproutEmail()->campaignEmails->getCampaignEmailById($emailId);
		$campaignType  = sproutEmail()->campaignTypes->getCampaignTypeById($campaignEmail->campaignTypeId);

		$campaignEmail->dateSent = DateTimeHelper::currentTimeForDb();

		if (sproutEmail()->campaignEmails->saveCampaignEmail($campaignEmail, $campaignType))
		{
			$html = craft()->templates->render('sproutemail/_modals/response', array(
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
			$html = craft()->templates->render('sproutemail/_modals/response', array(
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
