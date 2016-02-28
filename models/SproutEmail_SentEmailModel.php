<?php
namespace Craft;

class SproutEmail_SentEmailModel extends BaseElementModel
{
	public $saveAsNew;
	protected $fields;
	const ELEMENT_TYPE = 'SproutEmail_SentEmail';
	/**
	 * The element type this model is associated with
	 *
	 * @var string
	 */
	protected $elementType = 'SproutEmail_SentEmail';

	/**
	 * These have to be explicitly defined in order for the plugin to install
	 *
	 * @return array
	 */
	public function defineAttributes()
	{
		$defaults = parent::defineAttributes();
		$attributes = array(
			'title'        => array(AttributeType::Mixed, 'required' => false),
			'emailSubject' => array(AttributeType::Mixed, 'required' => false),
			'fromEmail'    => array(AttributeType::Mixed, 'required' => false),
			'fromName'     => array(AttributeType::Mixed, 'required' => false),
			'toEmail'      => array(AttributeType::Mixed, 'required' => false),
			'body'         => array(AttributeType::Mixed, 'required' => false),
			'htmlBody'     => array(AttributeType::Mixed, 'required' => false),
		  'info'         => array(AttributeType::Mixed, 'required' => false)
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

	public function getInfoRow()
	{
		$id = $this->id;

		return SproutEmail()->sentEmails->getInfoRow($id);
	}
}
