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
		$tableName = "sproutemail_sentemail";

		if ($table = $this->dbConnection->schema->getTable('{{' . $tableName . '}}'))
		{
			if ($table->getColumn('info') != null)
			{
				$sentEmails = craft()->db->createCommand()
					->select('id, info')
					->from($tableName)
					->queryAll();

				if ($count = count($sentEmails))
				{
					foreach ($sentEmails as $sentEmail)
					{
						$oldInfo = $sentEmail['info'];
						$newInfo = str_replace("testEmail", "deliveryType", $oldInfo);

						craft()->db->createCommand()->update($tableName, array(
							'info' => $newInfo ),
							'id= :id',
							array(
								':id' => $sentEmail['id']
							)
						);
					}
				}
			}
		}

		return true;
	}
}
