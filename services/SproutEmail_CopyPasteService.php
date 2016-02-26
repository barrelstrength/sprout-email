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
	 * @param SproutEmail_EntryModel    $entry
	 * @param SproutEmail_CampaignModel $campaign
	 *
	 * @return SproutEmail_ResponseModel
	 */
	public function exportEntry(SproutEmail_EntryModel $entry, SproutEmail_CampaignModel $campaign)
	{
		$params = array(
			'entry'     => $entry,
			'campaign'  => $campaign,
			'recipient' => array(
				'firstName' => 'John',
				'lastName'  => 'Doe',
				'email'     => 'john@doe.com'
			)
		);
		$html   = sproutEmail()->renderSiteTemplateIfExists($campaign->template, $params);
		$text   = sproutEmail()->renderSiteTemplateIfExists($campaign->template.'.txt', $params);
		$vars   = array(
			'html' => $html,
			'text' => $text,
		);

		$response = new SproutEmail_ResponseModel();
		$response->success = true;
		$response->content = craft()->templates->render('sproutemail/settings/_mailers/copypaste/prepare', $vars);

		return $response;
	}

	public function previewEntry(SproutEmail_EntryModel $entry, SproutEmail_CampaignModel $campaign)
	{
		$type   = craft()->request->getPost('contentType', 'html');
		$ext    = strtolower($type) == 'text' ? '.txt' : null;
		$params = array('entry' => $entry, 'campaign' => $campaign);
		$body   = sproutEmail()->renderSiteTemplateIfExists($campaign->template.$ext, $params);

		return array('content' => TemplateHelper::getRaw($body));
	}
}
