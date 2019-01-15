<?php

namespace Craft;

class m160303_000000_sproutEmail_updateNotificationOptionsFormat extends BaseMigration
{
	/**
	 * @return bool
	 */
	public function safeUp()
	{
		if (($table = $this->dbConnection->schema->getTable('{{sproutemail_campaigns_notifications}}')))
		{
			if ($table->getColumn('options') != null)
			{
				$notifications = craft()->db->createCommand()
					->select('id, eventId, options')
					->from('sproutemail_campaigns_notifications')
					->where(array('in', 'eventId', array('entries-saveEntry', 'users-saveUser')))
					->queryAll();

				if (is_array($notifications) && count($notifications))
				{
                    $count = count($notifications);
					SproutEmailPlugin::log('Notifications found: ' . $count, LogLevel::Info, true);

					$newOptions = array();

					foreach ($notifications as $notification)
					{
						switch ($notification['eventId'])
						{
							case 'entries-saveEntry':

								SproutEmailPlugin::log('Migrating Craft saveEntry notification', LogLevel::Info, true);

								$newOptions = $this->_updateSaveEntryOptions($notification['options']);
								break;

							case 'users-saveUser':

								SproutEmailPlugin::log('Migrating Craft saveUser notification', LogLevel::Info, true);

								$newOptions = $this->_updateSaveUserOptions($notification['options']);
								break;
						}

						craft()->db->createCommand()->update('sproutemail_campaigns_notifications', array(
							'options' => $newOptions
						), 'id= :id', array(':id' => $notification['id'])
						);

						SproutEmailPlugin::log('Migration of notification complete', LogLevel::Info, true);
					}
				}

				SproutEmailPlugin::log('No notifications found to migrate.', LogLevel::Info, true);
			}
			else
			{
				SproutEmailPlugin::log('Could not find the `options` column.', LogLevel::Info, true);
			}
		}
		else
		{
			SproutEmailPlugin::log('Could not find the `sproutemail_campaigns_notifications` table.', LogLevel::Info, true);
		}

		return true;
	}

	private function _updateSaveEntryOptions($options)
	{
		$oldOptions = JsonHelper::decode($options);

		// Check for new settings format
		if (isset($options['craft']['saveEntry']))
		{
			return $options;
		}

		// Check for old settings format
		if (isset($oldOptions['entriesSaveEntryOnlyWhenNew']) OR isset($oldOptions['entriesSaveEntrySectionIds']))
		{
			// Otherwise, update older format
			$whenNew    = isset($oldOptions['entriesSaveEntryOnlyWhenNew']) ? $oldOptions['entriesSaveEntryOnlyWhenNew'] : '';
			$sectionIds = isset($oldOptions['entriesSaveEntrySectionIds']) ? $oldOptions['entriesSaveEntrySectionIds'] : '';

			$newOptions = array(
				'craft' => array(
					'saveEntry' => array(
						'whenNew'     => $whenNew,
						'whenUpdated' => '',
						'sectionIds'  => $sectionIds
					)
				)
			);

			return JsonHelper::encode($newOptions);
		}

		// If we get this far, use what we have
		return $options;
	}

	private function _updateSaveUserOptions($options)
	{
		$oldOptions = JsonHelper::decode($options);

		// Check for new settings format
		if (isset($options['craft']['saveUser']))
		{
			return $options;
		}

		// Check for old settings format
		if (isset($oldOptions['usersSaveUserOnlyWhenNew']) OR isset($oldOptions['usersSaveUserGroupIds']))
		{
			$whenNew      = isset($oldOptions['usersSaveUserOnlyWhenNew']) ? $oldOptions['usersSaveUserOnlyWhenNew'] : '';
			$userGroupIds = isset($oldOptions['usersSaveUserGroupIds']) ? $oldOptions['usersSaveUserGroupIds'] : '';

			$newOptions = array(
				'craft' => array(
					'saveUser' => array(
						'whenNew'      => $whenNew,
						'whenUpdated'  => '',
						'userGroupIds' => $userGroupIds
					)
				)
			);

			return JsonHelper::encode($newOptions);
		}

		// If we get this far, use what we have
		return $options;
	}
}
