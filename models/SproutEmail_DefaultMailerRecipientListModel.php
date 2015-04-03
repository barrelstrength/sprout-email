<?php
namespace Craft;

/**
 * Class SproutEmail_DefaultMailerRecipientListModel
 *
 * @package Craft
 *
 * @property int    $id
 * @property string $name
 * @property string $handle
 * @property SproutEmail_DefaultMailerRecipientModel[]
 */
class SproutEmail_DefaultMailerRecipientListModel extends BaseModel
{
	public function defineAttributes()
	{
		return array(
			'id'         => AttributeType::Number,
			'name'       => array(AttributeType::String, 'required' => true),
			'handle'     => array(AttributeType::String, 'required' => false),
			'recipients' => array(AttributeType::Mixed, 'required' => false),
		);
	}
}
