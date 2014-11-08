<?php
namespace Craft;

class SproutEmail_EmailBlastModel extends BaseElementModel
{
	protected $elementType = 'SproutEmail_EmailBlast';
	
	private $_fields;

	/**
	 * Disabled - Email Blast Type isn't even setup properly
	 * Pending -  Email Blast Type is setup but Email Blast is disabled
	 * Ready -    Email Blast Type is setup and is enabled
	 * Archived - has been sent, or exported and manually marked archived
	 * 
	 */
	const DISABLED = 'expired'; // this doesn't behave properly when named 'disabled'
	const PENDING  = 'pending';
	const READY    = 'live';
	const ARCHIVED = 'setup';


	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), array(
			'id'               => AttributeType::Number,
			'emailBlastTypeId' => AttributeType::Number,
			'subjectLine'      => AttributeType::String,
			'sent'             => AttributeType::Bool,
		));
	}

	/*
	 * Returns the field layout used by this element.
	 *
	 * @return FieldLayoutModel|null
	 */
	public function getFieldLayout()
	{
		$emailBlastType = craft()->sproutEmail->getEmailBlastTypeById($this->emailBlastTypeId);

		return $emailBlastType->getFieldLayout();
	}

	// public function getUrlFormat()
	// {
		// $group = $this->getGroup();

		// if ($group && $group->hasUrls)
		// {
		// 	$groupLocales = $group->getLocales();

		// 	if (isset($groupLocales[$this->locale]))
		// 	{
		// 		if ($this->level > 1)
		// 		{
		// 			return $groupLocales[$this->locale]->nestedUrlFormat;
		// 		}
		// 		else
		// 		{
		// 			return $groupLocales[$this->locale]->urlFormat;
		// 		}
		// 	}
		// }
		// 
	// }

	/**
	 * Pending -  has all required attributes and is disabled or 
	 * 	          does not have all required attributes
	 * Ready -    has all required attributes, and is enabled
	 * Archived - has been sent, or exported and manually marked archived
	 */
	public function getStatus()
	{
		$status = parent::getStatus();

		// Required attributes :$emailBlastType->emailProvider && 
		// 											$emailBlastType->textTemplate
		// Enabled : static::ENABLED
		// Disabled : static::DISABLED
		// Archived : static::ARCHIVED
		// Sent (track sent dates in a sent log table)
		// 
		// @TODO - we can make this conditional statement more
		// advanced and check for the Service Provider and determine
		// specific things about each service provider to decide if an
		// email is ready or not.  For now, we'll just check to see if 
		// it has a service provider and text template.
		
		$emailBlastType = craft()->sproutEmail->getEmailBlastTypeById($this->emailBlastTypeId);

		$hasRequiredAttributes = ($emailBlastType->emailProvider && $emailBlastType->textTemplate);

		$hasBeenSent = $this->sent; // @TODO - hard coded

		// Archived
		if ($status == static::ARCHIVED OR $hasBeenSent)
		{
			return static::ARCHIVED;
		}

		// Ready and Pending
		if ($hasRequiredAttributes && $status == static::ENABLED)
		{
			return static::READY;	
		}
		else
		{
			if ($hasRequiredAttributes) 
			{
				return static::PENDING;
			}
			else
			{
				return static::DISABLED;
			}
		}

		return $status;
	}

	/**
	 * Returns the fields associated with this form.
	 *
	 * @return array
	 */
	public function getFields()
	{
		if (!isset($this->_fields))
		{
			$this->_fields = array();

			$fieldLayoutFields = $this->getFieldLayout()->getFields();

			foreach ($fieldLayoutFields as $fieldLayoutField)
			{
				$field = $fieldLayoutField->getField();
				$field->required = $fieldLayoutField->required;
				$this->_fields[] = $field;
			}
		}

		return $this->_fields;
	}

	/*
	 * Sets the fields associated with this form.
	 *
	 * @param array $fields
	 */
	public function setFields($fields)
	{
		$this->_fields = $fields;
	}

	/**
	 * Returns the element's CP edit URL.
	 *
	 * @return string|false
	 */
	public function getCpEditUrl()
	{
		$url = UrlHelper::getCpUrl('sproutemail/emailblasts/edit/'. $this->id);

		return $url;
	}
}