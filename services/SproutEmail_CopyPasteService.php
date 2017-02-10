<?php
namespace Craft;

class SproutEmail_CopyPasteService extends BaseApplicationComponent
{
	protected $settings;

	public function setSettings($settings)
	{
		$this->settings = $settings;
	}

	/**
	 * @param SproutEmail_CampaignEmailModel $campaignEmail
	 * @param SproutEmail_CampaignTypeModel  $campaignType
	 *
	 * @return SproutEmail_ResponseModel
	 */
	public function sendCampaignEmail(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignTypeModel $campaignType)
	{
		$variables = array(
			'email'        => $campaignEmail,
			'campaignType' => $campaignType,

			// @deprecate - `entry` in favor of `email` in v3
			// @deprecate - `campaign` in favor of `campaignType` in v3
			'entry'        => $campaignEmail,
			'campaign'     => $campaignType
		);

		$html = sproutEmail()->renderSiteTemplateIfExists($campaignType->template, $variables);
		$text = sproutEmail()->renderSiteTemplateIfExists($campaignType->template . '.txt', $variables);

		$response          = new SproutEmail_ResponseModel();
		$response->success = true;
		$response->content = craft()->templates->render('sproutemail/settings/mailers/copypaste/sendEmailPrepare', array(
			'html' => trim($html),
			'text' => trim($text),
		));

		return $response;
	}
}
