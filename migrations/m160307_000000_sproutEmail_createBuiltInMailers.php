<?php

namespace Craft;

class m160307_000000_sproutEmail_createBuiltInMailers extends BaseMigration
{
	/**
	 * @return bool
	 */
	public function safeUp()
	{

		if ($table = craft()->db->schema->getTable('{{sproutemail_mailers}}'))
		{
			$names = craft()->db->createCommand()
				->select('name')
				->from('sproutemail_mailers')
				->queryAll();

			$existingMailers = array();

			if (!empty($names))
			{
				foreach ($names as $name)
				{
					$existingMailers[] = $name['name'];
				}
			}

			$settings = array();

			$settings['copypaste']['model'] = "Craft\\Model";

			$settings['mailchimp']['apiKey']    = null;
			$settings['mailchimp']['inlineCss'] = 1;
			$settings['mailchimp']['model']     = "Craft\\Model";

			$settings['campaignmonitor']['clientId'] = null;
			$settings['campaignmonitor']['apiKey']   = null;
			$settings['campaignmonitor']['model']    = "Craft\\Model";

			foreach ($settings as $id => $setting)
			{
				if (!in_array($id, $existingMailers))
				{
					craft()->db->createCommand()->insert('sproutemail_mailers',
						array(
							'name'     => $id,
							'settings' => JsonHelper::encode($setting)
						)
					);
				}
			}
		}

		return true;
	}
}