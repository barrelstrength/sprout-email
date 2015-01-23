<?php
namespace Craft;

/**
 * Class SproutEmail_ResponseModel
 *
 * @package Craft
 *
 * @property bool    $success Whether or not the request was successful
 * @property string  $message The success or error message
 * @property string  $content Rendered HTML content of body
 * @property string  $action  Rendered HTML content of action buttons
 */
class SproutEmail_ResponseModel extends BaseModel
{
	public function defineAttributes()
	{
		return array(
			'success' => array(AttributeType::Bool, 'default' => false),
			'message' => array(AttributeType::Bool, 'default' => null),
			'content' => array(AttributeType::String, 'required' => true),
			'action'  => array(AttributeType::String, 'required' => false),
		);
	}
}
