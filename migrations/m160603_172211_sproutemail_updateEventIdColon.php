<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m160603_172211_sproutemail_updateEventIdColon extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$tableName = "sproutemail_campaigns_notifications";

		if ($table = $this->dbConnection->schema->getTable('{{' . $tableName . '}}'))
		{
			if ($table->getColumn('eventId') != null)
			{
				$notifications = craft()->db->createCommand()
					->select('id, eventId')
					->from($tableName)
					->queryAll();

				if ($count = count($notifications))
				{
					foreach ($notifications as $notification)
					{
						$oldEventId = $notification['eventId'];
						$newEventId = str_replace(":", "-", $oldEventId);

						craft()->db->createCommand()->update($tableName, array(
							'eventId' => $newEventId ),
							'id= :id',
							array(
								':id' => $notification['id']
							)
						);
					}
				}
			}
		}

		return true;
	}
}
