<?php

namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m170418_000001_sproutEmail_migrateRecipientListsToSproutLists extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		//	Check if Sprout Lists is installed
		$sproutLists = craft()->plugins->getPlugin('sproutLists', false);

		// Check if Sprout Email has Recipient Lists
		$recipientLists = craft()->db->createCommand()
			->select('*')
			->from('sproutemail_defaultmailer_recipientlists')
			->queryAll();

		// If both true, then
		if ($sproutLists && $sproutLists->isInstalled && is_array($recipientLists) && count($recipientLists))
		{
			$count      = 0;
			$listsByKey = array();

			// Migrate Recipient Lists to craft_sproutlists_lists
			foreach ($recipientLists as $recipientList)
			{
				$list         = new SproutLists_ListModel();
				$list->name   = $recipientList['name'];
				$list->handle = $recipientList['handle'];
				$list->type   = 'subscriber';

				$listType = sproutLists()->lists->getListType($list->type);

				$listType->saveList($list);

				$listsByKey[$recipientList['id']]                 = $recipientList;
				$listsByKey[$recipientList['id']]['newElementId'] = $list->id;

				$count++;
			}

			// Get existing Subscribers
			$subscribers = craft()->db->createCommand()
				->select('*')
				->from('sproutemail_defaultmailer_recipients')
				->queryAll();

			// Get existing Subscriptions
			$subscriptions = craft()->db->createCommand()
				->select('*')
				->from('sproutemail_defaultmailer_recipientlistrecipients')
				->queryAll();

			$count            = 0;
			$subscribersByKey = array();

			// Migrate Recipients to craft_sproutlists_subscriptions
			foreach ($subscribers as $subscriber)
			{
				$listType = sproutLists()->lists->getListType($list->type);

				$subscriberModel            = new SproutLists_SubscriberModel();
				$subscriberModel->email     = $subscriber['email'];
				$subscriberModel->firstName = $subscriber['firstName'];
				$subscriberModel->lastName  = $subscriber['lastName'];

				$list         = new SproutLists_ListModel();
				$list->type   = 'subscriber';

				$listType->saveSubscriber($subscriberModel);

				$subscribersByKey[$subscriber['id']]                 = $subscriber;
				$subscribersByKey[$subscriber['id']]['newElementId'] = $subscriberModel->id;

				$count++;
			}

			// Migrate Subscriptions to craft_sproutlists_subscriptions
			foreach ($subscriptions as $subscription)
			{
				$subscriptionModel             = new SproutLists_SubscriptionModel();
				$subscriptionModel->listType   = 'subscriber';
				$subscriptionModel->listHandle = $listsByKey[$subscription['recipientListId']]['handle'];
				$subscriptionModel->listId     = $listsByKey[$subscription['recipientListId']]['newElementId'];
				$subscriptionModel->subscriberId = $subscribersByKey[$subscription['recipientId']]['newElementId'];
				$subscriptionModel->email      = $subscribersByKey[$subscription['recipientId']]['email'];
				$subscriptionModel->elementId  = $listsByKey[$subscription['recipientListId']]['newElementId'];

				$listType = sproutLists()->lists->getListType($subscriptionModel->listType);

				$result = $listType->subscribe($subscriptionModel);

				// Update our Subscriber counts
				$listType->updateTotalSubscribersCount();
			}

			// Since we already migrated listIds from sproutemail_campaigns_entries_recipientlists
			// We will grab the old ids from the listSettings column and update those reference
			// to the new elementIds
			$notificationEmails = craft()->db->createCommand()
				->select('*')
				->from('sproutemail_notificationemails')
				->queryAll();

			foreach ($notificationEmails as $notificationEmail)
			{
				$oldListIds = JsonHelper::decode($notificationEmail['listSettings']);
				$newListIds = array();

				if (isset($oldListIds['listIds']) && is_array($oldListIds['listIds']) && count($oldListIds['listIds']))
				{
					foreach ($oldListIds['listIds'] as $oldListId)
					{
						if (isset($listsByKey[$oldListId]['newElementId']))
						{
							$newListIds[] = $listsByKey[$oldListId]['newElementId'];
						}
					}

					$listSettings = array(
						'listIds' => $newListIds
					);

					craft()->db->createCommand()->update('sproutemail_notificationemails', array(
						'listSettings' => JsonHelper::encode($listSettings)
					),
						'id= :id', array(':id' => $notificationEmail['id'])
					);
				}
			}
		}

		// Clean up and remove old tables
		if (craft()->db->tableExists('sproutemail_defaultmailer_recipientlistrecipients'))
		{
			SproutEmailPlugin::log('Removing sproutemail_defaultmailer_recipientlistrecipients table');

			craft()->db->createCommand()->dropTable('sproutemail_defaultmailer_recipientlistrecipients');
		}

		if (craft()->db->tableExists('sproutemail_defaultmailer_recipients'))
		{
			SproutEmailPlugin::log('Removing sproutemail_defaultmailer_recipients table');

			craft()->db->createCommand()->dropTable('sproutemail_defaultmailer_recipients');
		}

		if (craft()->db->tableExists('sproutemail_defaultmailer_recipientlists'))
		{
			SproutEmailPlugin::log('Removing sproutemail_defaultmailer_recipientlists table');

			craft()->db->createCommand()->dropTable('sproutemail_defaultmailer_recipientlists');
		}
	}
}
