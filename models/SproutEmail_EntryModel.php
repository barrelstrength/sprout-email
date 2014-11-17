<?php
namespace Craft;

class SproutEmail_EntryModel extends BaseElementModel
{
	protected $elementType = 'SproutEmail_Entry';
	
	private $_fields;

	/**
	 * Disabled - Campaign isn't even setup properly
	 * Pending -  Campaign is setup but Entry is disabled
	 * Ready -    Campaign is setup and is enabled
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
			'campaignId' => AttributeType::Number,
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
		$campaign = craft()->sproutEmail_campaign->getCampaignById($this->campaignId);

		return $campaign->getFieldLayout();
	}

	public function getUrlFormat($template = null)
	{
		$campaign = $this->getType();
		
		if ($campaign && $campaign->hasUrls)
		{
			// @TODO
			// - need to sort out locales
			// - need to determine if HTML/Text tempalte is needed
			
			return $campaign->template;
		}
		
	}

	/**
	 * Pending -  has all required attributes and is disabled or 
	 * 	          does not have all required attributes
	 * Ready -    has all required attributes, and is enabled
	 * Archived - has been sent, or exported and manually marked archived
	 */
	public function getStatus()
	{
		$status = parent::getStatus();

		// Required attributes :$campaign->emailProvider && 
		// 											$campaign->template
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
		
		$campaign = craft()->sproutEmail_campaign->getCampaignById($this->campaignId);

		$hasRequiredAttributes = ($campaign->emailProvider && $campaign->template);

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

	public function getType()
	{
		$campaign = craft()->sproutEmail_campaign->getCampaignById($this->campaignId);
		
		return $campaign;
	}

	/**
	 * Returns the element's CP edit URL.
	 *
	 * @return string|false
	 */
	public function getCpEditUrl()
	{
		$url = UrlHelper::getCpUrl('sproutemail/entries/edit/'. $this->id);

		return $url;
	}
}