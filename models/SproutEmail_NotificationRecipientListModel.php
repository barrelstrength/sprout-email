<?php
namespace Craft;

/**
 * Class SproutEmail_EntryRecipientListModel
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
