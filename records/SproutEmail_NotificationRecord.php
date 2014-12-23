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
class SproutEmail_NotificationRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'sproutemail_campaigns_notifications';
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'eventId'    => array(AttributeType::String, 'require' => true),
			'campaignId' => array(AttributeType::Number, 'require' => true),
			'options'    => AttributeType::Mixed,
		);
	}
}
