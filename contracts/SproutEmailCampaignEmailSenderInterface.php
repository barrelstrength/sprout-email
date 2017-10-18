<?php

namespace Craft;

/**
 * Interface SproutEmailCampaignEmailSenderInterface
 *
 * @package Craft
 */
interface SproutEmailCampaignEmailSenderInterface
{
	/**
	 * @param SproutEmail_CampaignEmailModel $campaignEmail
	 * @param SproutEmail_CampaignTypeModel  $campaign
	 *
	 * @return mixed
	 * @internal param SproutEmail_CampaignEmailModel $campaignEmail
	 */
	public function sendCampaignEmail(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignTypeModel $campaignType);
}
