<?php
namespace Craft;

/**
 * Interface SproutEmailNotificationSenderInterface
 *
 * @package Craft
 */
interface SproutEmailNotificationSenderInterface
{
	/**
	 * @param SproutEmail_EntryModel    $entry
	 * @param SproutEmail_CampaignModel $campaign
	 *
	 * @return mixed
	 */
	public function sendNotification(SproutEmail_EntryModel $entry, SproutEmail_CampaignModel $campaign);
}
