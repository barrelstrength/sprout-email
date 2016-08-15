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
	public function exportEmail(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignTypeModel $campaignType)
	{
		$params = array(
			'email'     => $campaignEmail,
			'campaign'  => $campaignType,
			'recipient' => array(
				'firstName' => 'John',
				'lastName'  => 'Doe',
				'email'     => 'john@doe.com'
			),

			// @deprecate - in favor of `email` in v3
			'entry'     => $campaignEmail
		);

		$html = sproutEmail()->renderSiteTemplateIfExists($campaignType->templateCopyPaste, $params);
		$text = sproutEmail()->renderSiteTemplateIfExists($campaignType->templateCopyPaste . '.txt', $params);

		$vars = array(
			'html' => trim($html),
			'text' => trim($text),
		);

		$response          = new SproutEmail_ResponseModel();
		$response->success = true;

		$response->content = craft()->templates->render('sproutemail/settings/mailers/copypaste/prepare', $vars);

		return $response;
	}

	public function previewCampaignEmail(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignTypeModel $campaignType)
	{
		$type   = craft()->request->getPost('contentType', 'html');
		$ext    = strtolower($type) == 'text' ? '.txt' : null;
		$params = array(
			'entry' => $campaignEmail,
			'campaign' => $campaignType
		);
		$body   = sproutEmail()->renderSiteTemplateIfExists($campaignType->template . $ext, $params);

		return array('content' => TemplateHelper::getRaw($body));
	}
}
