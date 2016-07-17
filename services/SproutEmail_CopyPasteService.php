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
	 * @param SproutEmail_CampaignEmailModel    $entry
	 * @param SproutEmail_CampaignModel $campaign
	 *
	 * @return SproutEmail_ResponseModel
	 */
	public function exportEntry(SproutEmail_CampaignEmailModel $entry, SproutEmail_CampaignModel $campaign)
	{
		$params = array(
			'email'     => $entry,
			'campaign'  => $campaign,
			'recipient' => array(
				'firstName' => 'John',
				'lastName'  => 'Doe',
				'email'     => 'john@doe.com'
			),

			// @deprecate - in favor of `email` in v3
			'entry'     => $entry
		);

		$html = sproutEmail()->renderSiteTemplateIfExists($campaign->templateCopyPaste, $params);
		$text = sproutEmail()->renderSiteTemplateIfExists($campaign->templateCopyPaste . '.txt', $params);

		$vars = array(
			'html' => trim($html),
			'text' => trim($text),
		);

		$response = new SproutEmail_ResponseModel();
		$response->success = true;

		$response->content = craft()->templates->render('sproutemail/settings/_mailers/copypaste/prepare', $vars);

		return $response;
	}

	public function previewEntry(SproutEmail_CampaignEmailModel $entry, SproutEmail_CampaignModel $campaign)
	{
		$type = craft()->request->getPost('contentType', 'html');
		$ext = strtolower($type) == 'text' ? '.txt' : null;
		$params = array('entry' => $entry, 'campaign' => $campaign);
		$body = sproutEmail()->renderSiteTemplateIfExists($campaign->template . $ext, $params);

		return array('content' => TemplateHelper::getRaw($body));
	}
}
