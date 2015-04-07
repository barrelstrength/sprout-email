<?php
namespace Craft;

/**
 * Class SproutEmail_EntryRecipientListRecord
 *
 * @package Craft
 * --
 * @property int    $entryId
 * @property string $mailer
 * @property string $list
 * @property string $type
 */
class SproutEmail_EntryRecipientListRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'sproutemail_campaigns_entries_recipientlists';
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'mailer' => AttributeType::String,
			'list'   => AttributeType::String,
			'type'   => AttributeType::String,
		);
	}

	public function defineRelations()
	{
		return array(
			'entry' => array(
				static::BELONGS_TO,
				'SproutEmail_EntryRecord',
				'entryId',
				'required' => true,
				'onDelete' => static::CASCADE,
			)
		);
	}
}
