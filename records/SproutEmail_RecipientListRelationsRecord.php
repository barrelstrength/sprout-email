<?php
namespace Craft;

/**
 * Class SproutEmail_RecipientListRelationsRecord
 *
 * @package Craft
 * --
 * @property int    $emailId
 * @property string $mailer
 * @property string $list
 * @property string $type
 */
class SproutEmail_RecipientListRelationsRecord extends BaseRecord
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
		);
	}

	public function defineRelations()
	{
		return array(
			'element' => array(
				static::BELONGS_TO,
				'ElementRecord',
				'emailId',
				'required' => true,
				'onDelete' => static::CASCADE,
			)
		);
	}

	public function defineIndexes()
	{
		return array(
			array('columns' => array('emailId'))
		);
	}
}
