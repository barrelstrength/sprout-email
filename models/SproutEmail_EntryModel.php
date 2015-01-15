<?php
namespace Craft;

/**
 * Class SproutEmail_EntryModel
 *
 * @package Craft
 * --
 * @property int    $id
 * @property string $subjectLine
 * @property int    $campaignId
 * @property string $fromName
 * @property string $fromEmail
 * @property string $replyTo
 * @property bool   $sent
 */
class SproutEmail_EntryModel extends BaseElementModel
{
	protected $fields;
	protected $elementType = 'SproutEmail_Entry';

	/**
	 * Disabled - Campaign isn't even setup properly
	 * Pending -  Campaign is setup but Entry is disabled
	 * Ready -    Campaign is setup and is enabled
	 * Archived - has been sent, or exported and manually marked archived
	 */
	const READY = 'live';
	const PENDING = 'pending';
	const DISABLED = 'expired'; // this doesn't behave properly when named 'disabled'
	const ARCHIVED = 'setup';

	/**
	 * @return array
	 */
	protected function defineAttributes()
	{
		$defaults   = parent::defineAttributes();
		$attributes = array(
			'subjectLine'     => array(AttributeType::String, 'required' => true),
			'campaignId'      => array(AttributeType::Number, 'required' => true),
			'fromName'        => array(AttributeType::String, 'minLength' => 2, 'maxLength' => 100, 'required' => true),
			'fromEmail'       => array(AttributeType::Email, 'required' => true),
			'replyTo'         => array(AttributeType::Email, 'required' => false),
			'sent'            => AttributeType::Bool,
			// @related
			'recipientLists'   => Attributetype::Mixed,
		);

		return array_merge($defaults, $attributes);
	}

	/*
	 * @return FieldLayoutModel|null
	 */
	public function getFieldLayout()
	{
		$campaign = sproutEmail()->campaigns->getCampaignById($this->campaignId);

		return $campaign->getFieldLayout();
	}

	public function getUrlFormat($template = null)
	{
		$campaign = $this->getType();

		if ($campaign && $campaign->hasUrls)
		{
			return $campaign->template.'/{slug}';
		}
	}

	/**
	 * Pending -  has all required attributes and is disabled or
	 *              does not have all required attributes
	 * Ready -    has all required attributes, and is enabled
	 * Archived - has been sent, or exported and manually marked archived
	 */
	public function getStatus()
	{
		$status = parent::getStatus();

		// Required attributes :$campaign->mailer && $campaign->template
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

		$campaign = sproutEmail()->campaigns->getCampaignById($this->campaignId);

		$hasRequiredAttributes = ($campaign->mailer && $campaign->template);

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

	public function getType()
	{
		$campaign = sproutEmail()->campaigns->getCampaignById($this->campaignId);

		return $campaign;
	}

	/**
	 * Returns the element's CP edit URL.
	 *
	 * @return string|false
	 */
	public function getCpEditUrl()
	{
		$url = UrlHelper::getCpUrl('sproutemail/entries/edit/'.$this->id);

		return $url;
	}
}
