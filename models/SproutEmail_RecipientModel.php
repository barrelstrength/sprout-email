<?php
namespace Craft;

/**
 * Recipient model
 * We're only using this for email validation
 */
class SproutEmail_RecipientModel extends BaseModel
{
	/**
	 * We're only using this for email validation
	 *
	 * @return array
	 */
	public function defineAttributes()
	{
		return array (
			'email' => AttributeType::Email
		);
	}
}