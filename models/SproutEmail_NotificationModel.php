<?php
namespace Craft;

/**
 * Class SproutEmail_NotificationModel
 *
 * @package Craft
 * --
 * @property string $eventId
 * @property int    $campaignId
 * @property array  $options
 */
class SproutEmail_NotificationModel extends BaseModel
{
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
