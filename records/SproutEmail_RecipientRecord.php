<?php
namespace Craft;

class SproutEmail_RecipientRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'sproutemail_recipients';
	}

	public function defineAttributes()
	{
		return array(
			'email'	=> AttributeType::String,
			'firstName' => AttributeType::String,
			'lastName' => AttributeType::String,
		);
	}

	public function defineRelations()
	{
		return array(
			'groups' => array(
				static::MANY_MANY,
				'SproutEmail_RecipientGroupRecord',
				'sproutemail_recipientgroups_recipients_assignments(recipientId, recipientGroupId)'
			)
		);
	}
}