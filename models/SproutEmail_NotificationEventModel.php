<?php
namespace Craft;

/**
 * Notification event model
 */
class SproutEmail_NotificationEventModel extends BaseModel
{
	/**
	 * These have to be explicitly defined in order for the plugin to install
	 *
	 * @return array
	 */
	public function defineAttributes()
	{
		return array (
			// emailBlastType info
			'id' => array (
				AttributeType::Number 
			),
			'registrar' => array (
				AttributeType::String 
			),
			'event' => array (
				AttributeType::String 
			),
			'description' => array (
				AttributeType::String 
			) 
		);
	}
}