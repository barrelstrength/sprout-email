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
	 * @param SproutEmail_CampaignTypeModel $campaign
	 * @param null                          $object
	 *
	 * @return mixed
	 * @internal param mixed|null $element The element/object involved when triggering the event
	 *
	 * @example
	 * 1. entries.saveEntry > $object = EntryModel
	 * 2. users.saveUser    > $object = UserModel
	 * 3. userSession.login > $object = 'username'
	 *
	 * @note
	 * In case 3 above, we actually get a string (username) back but we fetch a user model based on that
	 *
	 */
	public function sendNotification($campaign, $object = null);
}
