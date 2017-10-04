<?php
namespace Craft;

/**
 * Class SproutEmail_MailerRecord
 *
 * @package Craft
 * --
 * @property string $name
 * @property array  $settings
 */
class SproutEmail_MailerRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'sproutemail_mailers';
	}

	public function defineAttributes()
	{
		return array(
			'name'     => array(AttributeType::String, 'required' => true),
			'settings' => array(AttributeType::Mixed, 'default' => array()),
		);
	}
}
