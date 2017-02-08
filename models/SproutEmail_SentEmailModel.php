<?php
namespace Craft;

class SproutEmail_SentEmailModel extends BaseElementModel
{
	public $saveAsNew;

	protected $fields;

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
		$date = date_create($this->dateCreated->localeDate());

		return date_format($date,"M j, Y") . ' ' . $this->dateCreated->localeTime();
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
			'title'        => array(AttributeType::String, 'required' => false),
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

	/**
	 * Returns the element's CP edit URL.
	 *
	 * @return string|false
	 */
	public function getCpEditUrl()
	{
		$url = UrlHelper::getCpUrl('sproutemail/sentemail/edit/' . $this->id);

		return $url;
	}
}
