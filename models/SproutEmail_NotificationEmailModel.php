<?php
namespace Craft;

/**
 * Class SproutEmail_NotificationEmailModel
 *
 * @package Craft
 */
class SproutEmail_NotificationEmailModel extends BaseElementModel
{
	protected $fields;
	protected $elementType = 'SproutEmail_NotificationEmail';

	const ENABLED  = 'enabled';
	const PENDING  = 'pending';
	const DISABLED = 'disabled';
	const ARCHIVED = 'archived';

	public function defineAttributes()
	{
		$defaults = parent::defineAttributes();

		$attributes = array(
			'name'                  => array('type' => AttributeType::String, 'required' => true, 'minLength' => 2),
			'template'              => array('type' => AttributeType::String, 'required' => true, 'minLength' => 2),
			'eventId'               => AttributeType::String,
			'options'               => AttributeType::Mixed,
			'subjectLine'           => array(AttributeType::String, 'required' => true),
			'recipients'            => array(AttributeType::String, 'required' => false),
			'fromName'              => array('type' => AttributeType::String, 'required' => false, 'minLength' => 2),
			'fromEmail'             => array(AttributeType::String, 'required' => false),
			'replyToEmail'          => array(AttributeType::String, 'required' => false),
			'enableFileAttachments' => array(AttributeType::Bool, 'default' => false),
			'listSettings'          => AttributeType::Mixed,

			// @related
			'fieldLayoutId'         => AttributeType::Number
		);

		return array_merge($defaults, $attributes);
	}

	/**
	 * @return array
	 */
	public function behaviors()
	{
		return array(
			'fieldLayout' => new FieldLayoutBehavior('SproutEmail_NotificationEmail'),
		);
	}

	public function getFieldLayout()
	{
		return $this->asa('fieldLayout')->getFieldLayout();
	}

	/**
	 * Returns the fields associated with this form.
	 *
	 * @return array
	 */
	public function getFields()
	{
		if (!isset($this->fields))
		{
			$this->fields = array();

			$fieldLayoutFields = $this->getFieldLayout()->getFields();

			foreach ($fieldLayoutFields as $fieldLayoutField)
			{
				$field           = $fieldLayoutField->getField();
				$field->required = $fieldLayoutField->required;
				$this->fields[]  = $field;
			}
		}

		return $this->fields;
	}

	/*
	 * Sets the fields associated with this form.
	 *
	 * @param array $fields
	 */
	public function setFields($fields)
	{
		$this->fields = $fields;
	}

	public function getUrlFormat()
	{
		return "sproutemail/{slug}";
	}

	public function getUrl()
	{
		if ($this->uri !== null)
		{
			$url = UrlHelper::getSiteUrl($this->uri, null, null, $this->locale);

			return $url;
		}
	}

	/**
	 * @return false|string
	 */
	public function getCpEditUrl()
	{
		return UrlHelper::getCpUrl('sproutemail/notifications/edit/' . $this->id);
	}

	// To avoid throwing on exception. Error on phpunit
	public function __toString()
	{
		try
		{
			return parent::__toString();
		}
		catch (\Exception $exception)
		{
			return '';
		}
	}

	/**
	 * Returns the email status based on actual values and dynamic checking
	 *
	 * Disabled - Email is disabled
	 * Archived - Email has been manually set to archived
	 * Pending  - Email is enabled but some requirements are not yet met
	 * Enabled  - Email is enabled and all requirements are met
	 *
	 * @return string
	 */
	public function getStatus()
	{
		$status = parent::getStatus();

		switch ($status)
		{
			case BaseElementModel::DISABLED:
			{
				return static::DISABLED;

				break;
			}
			case BaseElementModel::ENABLED:
			{
				if (empty($this->template))
				{
					return static::PENDING;
				}

				return static::ENABLED;

				break;
			}
			case BaseElementModel::ARCHIVED:
			{
				return static::ARCHIVED;

				break;
			}
		}
	}

	public function isReady()
	{
		return (bool) ($this->getStatus() == static::ENABLED);
	}

	/**
	 * @param mixed|null $element
	 *
	 * @throws \Exception
	 * @return array|string
	 */
	public function getRecipients($element = null)
	{
		return sproutEmail()->getRecipients($element, $this);
	}

	/**
	 * Get the Notification Email Mailer
	 *
	 * @return mixed
	 */
	public function getMailer()
	{
		// All Notification Emails use the Default Mailer
		return sproutEmail()->mailers->getMailerByName('defaultmailer');
	}
}
