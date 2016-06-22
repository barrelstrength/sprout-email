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

	public function defineAttributes()
	{
		$defaults = parent::defineAttributes();

		$attributes = array(
			'name'        => array('type' => AttributeType::String, 'required' => true, 'minLength' => 2),
			'template'    => array('type' => AttributeType::String, 'required' => true, 'minLength' => 2),
			'eventId'     => AttributeType::String,
			'options'     => AttributeType::Mixed,
			'sent'        => AttributeType::Bool,
			'enableFileAttachments' => array(AttributeType::Bool, 'default' => false),
			'dateCreated' => AttributeType::DateTime,
			'dateUpdated' => AttributeType::DateTime,
			// @related
			'fieldLayoutId' => AttributeType::Number
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
				$field = $fieldLayoutField->getField();
				$field->required = $fieldLayoutField->required;
				$this->fields[] = $field;
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
}
