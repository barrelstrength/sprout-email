<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m160804_000009_sproutEmail_migrateCampaignEmails extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		SproutEmailPlugin::log('Updating all campaign email element types to SproutEmail_CampaignEmail');

		if (!craft()->db->tableExists('sproutemail_campaignemails'))
		{
			return false;
		}

		$entries = craft()->db->createCommand()
			->select('id')
			->from('sproutemail_campaignemails')
			->queryAll();

		if (!empty($entries))
		{
			foreach ($entries as $entry)
			{
				craft()->db->createCommand()->update('elements', array(
					'type' => 'SproutEmail_CampaignEmail'
				),
					'id= :id', array(':id' => $entry['id'])
				);
			}
		}

		SproutEmailPlugin::log('Finished updating all campaign email element types to SproutEmail_CampaignEmail');

		return true;
	}
}
