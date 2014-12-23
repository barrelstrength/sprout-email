<?php
namespace Craft;

class SproutEmail_RecipientGroupRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'sproutemail_recipientgroups';
	}

	public function defineAttributes()
	{
		return array(
			'name'	=> AttributeType::String,
			'mailer' => AttributeType::String,
		);
	}

	public function defineRelations()
	{
		return array(
			'recipients' => array(
				static::MANY_MANY,
				'SproutEmail_RecipientRecord',
				'sproutemail_recipientgroups_recipients_assignments(recipientGroupId, recipientId)'
			)
		);
	}
}
