<?php
namespace Craft;

/**
 * Class SproutEmail_EntryRecord
 *
 * @package Craft
 * --
 * @property int    $id
 * @property int    $campaignId
 * @property int    $recipientListId
 * @property string $subjectLine
 * @property string $fromName
 * @property string $fromEmail
 * @property string $replyTo
 * @property bool   $sent
 */
class SproutEmail_SentEntryRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'sproutemail_sent_entries';
	}

	/**
	 * @todo Device a way to store sender info as a relationship if it makes more sense
	 *
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'campaignEntryId'       => array(AttributeType::Number, 'required' => true),
			'campaignNotifcationId' => array(AttributeType::Number, 'required' => false)
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
//			'element'        => array(
//				static::BELONGS_TO,
//				'ElementRecord',
//				'id',
//				'required' => true,
//				'onDelete' => static::CASCADE
//			),
			'campaign'       => array(
				static::BELONGS_TO,
				'SproutEmail_EntryRecord',
				'campaignEntryId',
				'required' => true,
				'onDelete' => static::CASCADE
			),
			'notification' => array(
				static::BELONGS_TO,
				'SproutEmail_NotificationRecord',
				'campaignNotifcationId'
			)
		);
	}
}
