<?php

namespace Craft;

class SproutEmail_SentEmailModel extends BaseElementModel
{
	public $saveAsNew;

	protected $fields;

	public $enableFileAttachments;

	const ELEMENT_TYPE = 'SproutEmail_SentEmail';

	const SENT   = "sent";
	const FAILED = "failed";

	/**
	 * The element type this model is associated with
	 *
	 * @var string
	 */
	protected $elementType = 'SproutEmail_SentEmail';

	public function __toString()
	{
		return $this->getLocaleNiceDateTime();
	}

	public function getLocaleNiceDateTime()
	{
		return $this->dateCreated->format("M j, Y H:i A");
	}

	/**
	 * These have to be explicitly defined in order for the plugin to install
	 *
	 * @return array
	 */
	public function defineAttributes()
	{
		$defaults   = parent::defineAttributes();
		$attributes = array(
			'title'        => array(AttributeType::Mixed, 'required' => false),
			'emailSubject' => array(AttributeType::Mixed, 'required' => false),
			'fromEmail'    => array(AttributeType::Mixed, 'required' => false),
			'fromName'     => array(AttributeType::Mixed, 'required' => false),
			'toEmail'      => array(AttributeType::Mixed, 'required' => false),
			'body'         => array(AttributeType::Mixed, 'required' => false),
			'htmlBody'     => array(AttributeType::Mixed, 'required' => false),
			'info'         => array(AttributeType::Mixed, 'required' => false),
			'status'       => array(AttributeType::String, 'required' => false, 'default' => 'sent')
		);

		return array_merge($defaults, $attributes);
	}
}
