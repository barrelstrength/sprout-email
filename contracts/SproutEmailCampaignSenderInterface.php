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
	 * @param SproutEmail_CampaignEmailModel $campaignEmail
	 * @param SproutEmail_CampaignTypeModel  $campaign
	 *
	 * @return mixed
	 * @internal param SproutEmail_CampaignEmailModel $campaignEmail
	 */
	public function sendCampaign(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignTypeModel $campaign);

	/**
	 * @param SproutEmail_CampaignEmailModel $campaignEmail
	 * @param SproutEmail_CampaignTypeModel  $campaign
	 *
	 * @return mixed
	 * @internal param SproutEmail_CampaignEmailModel $campaignEmail
	 */
	public function previewCampaign(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignTypeModel $campaign);
}
