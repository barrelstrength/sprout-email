<?php
namespace Craft;

/**
 * Interface SproutEmailCampaignSenderInterface
 *
 * @package Craft
 */
interface SproutEmailCampaignSenderInterface
{
	/**
	 * @param SproutEmail_CampaignEmailModel    $entry
	 * @param SproutEmail_CampaignModel $campaign
	 *
	 * @return mixed
	 */
	public function sendCampaign(SproutEmail_CampaignEmailModel $entry, SproutEmail_CampaignModel $campaign);

	/**
	 * @param SproutEmail_CampaignEmailModel    $entry
	 * @param SproutEmail_CampaignModel $campaign
	 *
	 * @return mixed
	 */
	public function previewCampaign(SproutEmail_CampaignEmailModel $entry, SproutEmail_CampaignModel $campaign);
}
