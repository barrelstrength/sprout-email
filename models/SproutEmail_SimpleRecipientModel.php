<?php

namespace Craft;

/**
 * Class SproutEmail_SimpleRecipientModel
 *
 * @package Craft
 *
 * @property string $firstName
 * @property string $lastName
 * @property string $email
 */
class SproutEmail_SimpleRecipientModel extends BaseModel
{
	/**
	 * Returns an instance of self
	 *
	 * @param  array $attributes
	 *
	 * @return SproutEmail_SimpleRecipientModel
	 */
	public static function create(array $attributes = array())
	{
		$self = new self;

		$self->setAttributes($attributes);

		return $self;
	}

	public function defineAttributes()
	{
		return array(
			'firstName' => array(AttributeType::String),
			'lastName'  => array(AttributeType::String),
			'email'     => array(AttributeType::Email, 'required' => true),
		);
	}
}
