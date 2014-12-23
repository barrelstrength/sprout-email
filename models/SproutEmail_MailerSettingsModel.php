<?php
namespace Craft;

/**
 * Class SproutEmail_MailerSettingsModel
 *
 * @package Craft
 *--
 * @property string $name
 * @property array  $settings
 */
class SproutEmail_MailerSettingsModel extends BaseModel
{
	public function defineAttributes()
	{
		return array (
			'id'			=> AttributeType::Number,
			'name'			=> array(ttributeType::String, 'required' => true),
			'settings'		=> array(AttributeType::Mixed, 'default' => array()),
			'dateCreated'	=> AttributeType::DateTime,
			'dateUpdated'	=> AttributeType::DateTime,
		);
	}
}
