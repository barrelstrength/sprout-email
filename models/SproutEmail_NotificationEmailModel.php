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
class SproutEmail_NotificationEmailModel extends BaseElementModel
{

	public function defineAttributes()
	{
		$defaults = parent::defineAttributes();

		$attributes = array(
			'name'        => AttributeType::String,
			'handle'      => AttributeType::String,
			'eventId'     => array(AttributeType::String, 'require' => true),
			'campaignId'  => array(AttributeType::Number, 'require' => true),
			'options'     => AttributeType::Mixed,
			'urlFormat'   => AttributeType::String,
			'template'    => AttributeType::String,
			'sent'        => AttributeType::Bool,
			'enableFileAttachments' => array(AttributeType::Bool, 'default' => false),
			'dateCreated' => AttributeType::DateTime,
			'dateUpdated' => AttributeType::DateTime,
			// @related
			'fieldLayoutId' => AttributeType::Number
		);

		return array_merge($defaults, $attributes);
	}
}
