<?php
namespace Craft;

/**
 * Class SproutEmail_DefaultMailerRecipientListRecipientRecord
 *
 * @package Craft
 *
 * @property int $recipientId
 * @property int $recipientListId
 */
class SproutEmail_DefaultMailerRecipientListRecipientRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'sproutemail_defaultmailer_recipientlistrecipients';
	}

	public function defineAttributes()
	{
		return array(
			'recipientId'     => AttributeType::Number,
			'recipientListId' => AttributeType::Number,
		);
	}

	public function defineIndexes()
	{
		return array(
			array('columns' => array('recipientId', 'recipientListId'), 'unique' => true)
		);
	}
}
