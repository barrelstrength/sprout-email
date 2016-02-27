<?php
namespace Craft;

/**
 * Class SproutEmail_DefaultMailerRecipientRecord
 *
 * @package Craft
 *
 * @property string $email
 * @property string $firstName
 * @property string $lastName
 */
class SproutEmail_DefaultMailerRecipientRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'sproutemail_defaultmailer_recipients';
	}

	public function defineAttributes()
	{
		return array(
			'email'     => AttributeType::String,
			'firstName' => AttributeType::String,
			'lastName'  => AttributeType::String,
		);
	}

	public function defineRelations()
	{
		return array(
			'element'        => array(
				static::BELONGS_TO,
				'ElementRecord',
				'id',
				'required' => true,
				'onDelete' => static::CASCADE
			),
			'recipientLists' => array(
				static::MANY_MANY,
				'SproutEmail_DefaultMailerRecipientListRecord',
				'sproutemail_defaultmailer_recipientlistrecipients(recipientId, recipientListId)'
			)
		);
	}

	public function defineIndexes()
	{
		return array(
			array('columns' => array('email'), 'unique' => true)
		);
	}
}
