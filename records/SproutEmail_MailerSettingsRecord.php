<?php
namespace Craft;

/**
 * Class SproutEmail_MailerSettingsRecord
 *
 * @package Craft
 *
 * @property string $name
 * @property array  $settings
 */
class SproutEmail_MailerSettingsRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'sproutemail_mailers_settings';
	}

	public function defineAttributes()
	{
		return array(
			'name'     => array(AttributeType::String, 'required' => true),
			'settings' => array(AttributeType::Mixed, 'default' => array()),
		);
	}
}
