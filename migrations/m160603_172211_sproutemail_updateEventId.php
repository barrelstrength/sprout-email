<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m160603_172211_sproutemail_updateEventId extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$tableName  = 'sproutemail_campaigns_notifications';
		$columnName = 'eventId';
		$supportedEvents = array(
			'entries-saveEntry',
			'entries-deleteEntry',
			'userSession-login',
			'users-saveUser',
			'users-deleteUser',
			'users-activateUser',
			'commerce_orders-onOrderComplete',
			'commerce_transactions-onSaveTransaction',
			'commerce_orderHistories-onStatusChange'
		);

		if (craft()->db->tableExists($tableName))
		{
			if (craft()->db->columnExists($tableName, $columnName))
			{
				$notifications = craft()->db->createCommand()
					->select('id, eventId')
					->from($tableName)
					->queryAll();

				foreach ($notifications as $notification)
				{
					$oldEventId = $notification['eventId'];
					$newEventId = $oldEventId;

					if (strpos($oldEventId, ':') !== false)
					{
						$newEventId = str_replace(':', '-', $oldEventId);
					}
					else if (in_array($newEventId, $supportedEvents))
					{
						$newEventId = 'SproutEmail-'.$newEventId;
					}
					else
					{
						$prefix = explode('-', $newEventId);

						if (count($prefix) > 0)
						{
							$newEventId = ucfirst($prefix[0]).'-'.$newEventId;
						}
					}

					craft()->db->createCommand()->update($tableName, array(
						'eventId' => $newEventId ),
						'id= :id',
						array(
							':id' => $notification['id']
						)
					);
				}
			}
			else
			{
				SproutEmailPlugin::log("Column `$columnName` does not exists in the `$tableName` table.", LogLevel::Info, true);
			}
		}

		return true;
	}
}
