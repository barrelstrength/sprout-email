<?php
namespace Craft;

/**
 * Class SproutEmail_MailerModel
 *
 * @package Craft
 *--
 * @property string $name
 * @property array  $settings
 */
class SproutEmail_MailerModel extends BaseModel
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
