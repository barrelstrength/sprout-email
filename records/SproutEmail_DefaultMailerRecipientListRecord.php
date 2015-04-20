<?php
namespace Craft;

/**
 * Class SproutEmail_DefaultMailerRecipientListRecord
 *
 * @package Craft
 *
 * @property string $name
 * @property string $handle
 * @property bool   $dynamic Whether or not the list was created dynamically
 */
class SproutEmail_DefaultMailerRecipientListRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'sproutemail_defaultmailer_recipientlists';
	}

	public function defineAttributes()
	{
		return array(
			'name'    => AttributeType::String,
			'handle'  => AttributeType::String,
			'dynamic' => array(AttributeType::Bool, 'default' => 0, 'column' => ColumnType::TinyInt),
		);
	}

	public function defineRelations()
	{
		return array(
			'recipients' => array(
				static::MANY_MANY,
				'SproutEmail_DefaultMailerRecipientRecord',
				'sproutemail_defaultmailer_recipientlistrecipients(recipientListId, recipientId)'
			)
		);
	}

	public function defineIndexes()
	{
		return array(
			array('columns' => array('handle'), 'unique' => true)
		);
	}
}
