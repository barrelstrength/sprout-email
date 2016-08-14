<?php
namespace Craft;

/**
 * Class SproutEmail_RecipientListRelationsModel
 *
 * @package Craft
 * --
 * @property string $mailer
 * @property string $mailerListId
 */
class SproutEmail_RecipientListRelationsModel extends BaseModel
{
	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'id'      => AttributeType::Number,
			'emailId' => AttributeType::String,
			'mailer'  => AttributeType::String,
			'list'    => AttributeType::String
		);
	}
}
