<?php
namespace Craft;

class SproutEmail_RecipientGroupRecipientAssignmentRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'sproutemail_recipientgroups_recipients_assignments';
	}

	public function defineAttributes()
	{
		return array(
			'recipientId' => AttributeType::Number,
			'recipientGroupId' => AttributeType::Number,
		);
	}
}