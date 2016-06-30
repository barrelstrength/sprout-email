<?php
namespace Craft;

/**
 * Class SproutEmail_EntryRecipientListModel
 *
 * @package Craft
 * --
 * @property string $mailer
 * @property string $mailerListId
 * @property string $mailerListType
 */
class SproutEmail_NotificationRecipientListModel extends BaseModel
{
	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'id'             => AttributeType::Number,
			'notificationId' => AttributeType::Number,
			'list'           => AttributeType::String
		);
	}
}
