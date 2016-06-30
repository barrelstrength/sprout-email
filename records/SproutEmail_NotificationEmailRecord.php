<?php
namespace Craft;

/**
 * Class SproutEmail_NotificationEmailRecord
 *
 * @package Craft
 * --
 * @property string $eventId
 * @property int    $campaignId
 * @property array  $options
 */
class SproutEmail_NotificationEmailRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'sproutemail_notification_email';
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'name'        => array('type' => AttributeType::String, 'required' => true, 'minLength' => 2),
			'template'    => array('type' => AttributeType::String, 'required' => true, 'minLength' => 2),
			'eventId'     => AttributeType::String,
			'options'     => AttributeType::Mixed,
			'subjectLine'           => array(AttributeType::String, 'required' => true),
			'recipients'            => array(AttributeType::String, 'required' => false),
			'fromName'              => array('type' => AttributeType::String, 'required' => false, 'minLength' => 2),
			'fromEmail'             => array(AttributeType::String, 'required' => false),
			'replyToEmail'          => array(AttributeType::String, 'required' => false),
			'sent'        => AttributeType::Bool,
			'enableFileAttachments' => array(AttributeType::Bool, 'default' => false),
			'dateCreated' => AttributeType::DateTime,
			'dateUpdated' => AttributeType::DateTime,
			// @related
			'fieldLayoutId' => AttributeType::Number
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'element' => array(
				static::BELONGS_TO,
				'ElementRecord',
				'id',
				'required' => true,
				'onDelete' => static::CASCADE
			),
			'fieldLayout' => array(
				static::BELONGS_TO,
				'FieldLayoutRecord',
				'onDelete' => static::SET_NULL
			),
			'recipientLists' => array(
				static::HAS_MANY,
				'SproutEmail_NotificationRecipientListRecord',
				'notificationId'
			)
		);
	}
}
