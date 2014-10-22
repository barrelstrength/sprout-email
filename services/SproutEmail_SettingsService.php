<?php
namespace Craft;

class SproutEmail_SettingsService extends BaseApplicationComponent
{
	public function saveSettings($settings)
	{		
		$settings = JsonHelper::encode($settings);

		$affectedRows = craft()->db->createCommand()->update('plugins', array(
			'settings' => $settings
		), array(
			'class' => 'SproutEmail'
		));

		return (bool) $affectedRows;
	}
}