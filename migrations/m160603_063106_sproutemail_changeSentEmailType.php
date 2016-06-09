<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m160603_063106_sproutemail_changeSentEmailType extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$tableName  = "sproutemail_sentemail";
		$columnName = "info";

		if (craft()->db->tableExists($tableName))
		{
			if (craft()->db->columnExists($tableName, $columnName))
			{
				$sentEmails = craft()->db->createCommand()
					->select('id, info')
					->from($tableName)
					->queryAll();

				foreach ($sentEmails as $sentEmail)
				{
					$oldInfo = $sentEmail['info'];
					$newInfo = str_replace("testEmail", "deliveryType", $oldInfo);
					$data    = json_decode($newInfo, true);

					if (isset($data['deliveryType']) && ($data['deliveryType'] === 'Yes' || $data['deliveryType'] === 'Test Email'))
					{
						$data['deliveryType'] = "Test";
					}

					$newInfo = json_encode($data);

					craft()->db->createCommand()->update($tableName, array(
						'info' => $newInfo ),
						'id= :id',
						array(
							':id' => $sentEmail['id']
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
