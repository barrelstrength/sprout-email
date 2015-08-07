<?php
namespace Craft;

/**
 * Class SproutEmail_ResponseModel
 *
 * @package Craft
 *
 * @property bool   $success Whether or not the request was successful
 * @property string $message The success or error message
 * @property string $content Rendered HTML content of body
 * @property array  $vars    Template variables
 */
class SproutEmail_ResponseModel extends BaseModel
{
	/**
	 * @param string $template
	 * @param array  $vars
	 *
	 * @return SproutEmail_ResponseModel
	 */
	public static function createModalResponse($template = '', array $vars = array())
	{
		/** @var SproutEmail_ResponseModel $instance */
		$instance = get_called_class();
		$instance = new $instance();

		$instance->setAttribute('success', true);
		$instance->setAttributes($vars);

		if ($template && craft()->templates->doesTemplateExist($template))
		{
			$vars = array_merge($vars, $instance->getAttributes());

			$instance->content = craft()->templates->render($template, $vars);
		}

		return $instance;
	}

	/**
	 * @param string $template
	 * @param array  $vars
	 *
	 * @return SproutEmail_ResponseModel
	 */
	public static function createErrorModalResponse($template = null, array $vars = array())
	{
		/** @var SproutEmail_ResponseModel $instance */
		$instance = get_called_class();
		$instance = new $instance();

		$instance->setAttribute('success', false);
		$instance->setAttributes($vars);

		if ($template && craft()->templates->doesTemplateExist($template))
		{
			$vars = array_merge($vars, $instance->getAttributes());

			$instance->content = craft()->templates->render($template, $vars);
		}

		return $instance;
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'success' => array(AttributeType::Bool, 'default' => false),
			'message' => array(AttributeType::Bool, 'default' => null),
			'content' => array(AttributeType::String, 'required' => true),
		);
	}
}
