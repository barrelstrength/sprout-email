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
	 * @param SproutEmail_EntryModel    $entry
	 * @param SproutEmail_CampaignModel $campaign
	 *
	 * @return mixed
	 */
	public function sendCampaign(SproutEmail_EntryModel $entry, SproutEmail_CampaignModel $campaign);

	/**
	 * @param SproutEmail_EntryModel    $entry
	 * @param SproutEmail_CampaignModel $campaign
	 *
	 * @return mixed
	 */
	public function previewCampaign(SproutEmail_EntryModel $entry, SproutEmail_CampaignModel $campaign);
}
