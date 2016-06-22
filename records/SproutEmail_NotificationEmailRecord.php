<?php
namespace Craft;

/**
 * Class SproutEmail_NotificationRecord
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
			'fieldLayout'   => array(
				static::BELONGS_TO,
				'FieldLayoutRecord',
				'onDelete' => static::SET_NULL
			)
		);
	}
}
