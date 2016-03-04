<?php

namespace Craft;


class m160304_000000_sproutEmail_updateUserEntryCheckboxOption extends BaseMigration
{
	/**
	 * @return bool
	 */
	public function safeUp()
	{

		if($table = craft()->db->schema->getTable('{{sproutemail_campaigns_notifications}}'))
		{
			if ($table->getColumn('options') != null)
			{
				$notifications = craft()->db->createCommand()
					->select('id, options')
					->from('sproutemail_campaigns_notifications')
					->queryAll();

				if ($count = count($notifications))
				{
					foreach($notifications as $notification)
					{
						$options = JsonHelper::decode($notification['options']);


						// Update to select all if empty values
						if (empty($options['craft']['saveUser']['userGroupIds']))
						{
							$options['craft']['saveUser']['userGroupIds'] = "*";
						}

						if (empty($options['craft']['saveEntry']['sectionIds']))
						{
							$options['craft']['saveEntry']['sectionIds'] = "*";
						}
						craft()->db->createCommand()->update('sproutemail_campaigns_notifications',
							array( 'options' => JsonHelper::encode($options) ),
							'id= :id', array(':id' => $notification['id'])
						);
					}
				}
			}
		}

		return true;
	}
}