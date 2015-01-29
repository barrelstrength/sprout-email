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
	 * @param SproutEmail_CampaignModel $campaign
	 * @param BaseElementModel|null     $element The element involved when triggering the event
	 *
	 * @example
	 * 1. entries.saveEntry > $element = EntryModel
	 * 2. users.saveUser    > $element = UserModel
	 * 3. userSession.login > $element = UserModal
	 *
	 * @note
	 * In case 3 above, we actually get a string (username) back but we fetch a user model based on that
	 *
	 * @return mixed
	 */
	public function sendNotification(SproutEmail_CampaignModel $campaign, BaseElementModel $element = null);
}
