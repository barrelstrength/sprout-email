<?php
namespace Craft;

/**
 * Class SproutEmail_CampaignEmailModel
 *
 * @package Craft
 * --
 * @property int         $id
 * @property string      $subjectLine
 * @property int         $campaignTypeId
 * @property string      $fromName
 * @property string      $fromEmail
 * @property string      $replyToEmail
 * @property bool        $sent
 * --
 * @property string|null $uri
 * @property string      $slug
 * @property bool        $enabled
 */
class SproutEmail_CampaignEmailModel extends BaseElementModel
{
	public $saveAsNew;
	protected $fields;
	protected $elementType = 'SproutEmail_CampaignEmail';

	/**
	 * Disabled - Campaign isn't setup properly
	 * Pending -  Campaign is setup but Entry is disabled
	 * Ready -    Campaign is setup and is enabled
	 * Archived - has been sent, or exported and manually marked archived
	 *
	 * @todo - needs some testing
	 */
	const READY    = 'ready';
	const PENDING  = 'pending';
	const DISABLED = 'disabled'; // this doesn't behave properly when named 'disabled'
	const ARCHIVED = 'archived';

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
	 * @return array
	 */
	public function defineAttributes()
	{
		$defaults   = parent::defineAttributes();
		$attributes = array(
			'subjectLine'           => array(AttributeType::String, 'required' => true),
			'campaignTypeId'        => array(AttributeType::Number, 'required' => true),
			'recipients'            => array(AttributeType::String, 'required' => false),
			'fromName'              => array(AttributeType::String, 'minLength' => 2, 'maxLength' => 100, 'required' => false),
			'fromEmail'             => array(AttributeType::String, 'minLength' => 6, 'required' => false),
			'replyToEmail'          => array(AttributeType::String, 'required' => false),
			'sent'                  => AttributeType::Bool,
			'enableFileAttachments' => array(AttributeType::Bool, 'default' => false),

			// @related
			'recipientLists'        => Attributetype::Mixed,
		);

		return array_merge($defaults, $attributes);
	}

	/**
	 * @return array
	 */
	public function rules()
	{
		$rules = parent::rules();

		$rules[] = array('replyToEmail', 'validateEmailWithOptionalPlaceholder');
		$rules[] = array('fromEmail', 'validateEmailWithOptionalPlaceholder');

		return $rules;
	}

	/**
	 * Ensures that $attribute is a valid email address or a placeholder to be parsed later
	 *
	 * @param $attribute
	 */
	public function validateEmailWithOptionalPlaceholder($attribute)
	{
		$value = $this->{$attribute};

		if (strpos($value, '{') !== 0)
		{
			if (!filter_var($value, FILTER_VALIDATE_EMAIL))
			{
				$params = array(
					'attribute' => ($attribute == 'replyToEmail') ? 'Reply To' : 'From Email',
				);

				$this->addError($attribute, Craft::t('{attribute} is not a valid email address.', $params));
			}
		}
	}

	/*
	 * @return FieldLayoutModel|null
	 */
	public function getFieldLayout()
	{
		$campaignType = sproutEmail()->campaignTypes->getCampaignTypeById($this->campaignTypeId);

		return $campaignType->getFieldLayout();
	}

	/**
	 * Pending -  has all required attributes and is disabled or
	 *              does not have all required attributes
	 * Ready -    has all required attributes, and is enabled
	 * Archived - has been sent, or exported and manually marked archived
	 */

	/**
	 * Returns the entry status based on actual values and dynamic checking
	 *
	 * Disabled - Entry is disabled
	 * Archived - Entry has been manually set to archived
	 * Pending  - Entry is enabled but some requirements are not yet met
	 * Ready    - Entry is enabled and all requirements are met
	 *
	 * @return string
	 */
	public function getStatus()
	{
		$status   = parent::getStatus();
		$campaignType = sproutEmail()->campaignTypes->getCampaignTypeById($this->campaignTypeId);

		switch ($status)
		{
			case BaseElementModel::DISABLED:
			{
				return static::DISABLED;

				break;
			}
			case BaseElementModel::ENABLED:
			{
				if ($this->sent)
				{
					return static::ARCHIVED;
				}

				if (empty($campaignType->mailer) || empty($campaignType->template))
				{
					return static::PENDING;
				}

				return static::READY;

				break;
			}
			case BaseElementModel::ARCHIVED:
			{
				return static::ARCHIVED;

				break;
			}
		}
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

	/**
	 * @return string
	 */
	public function getUrlFormat()
	{
		$campaignType = sproutEmail()->campaignTypes->getCampaignTypeById($this->campaignTypeId);

		if ($campaignType && $campaignType->hasUrls)
		{
			return $campaignType->urlFormat;
		}
	}

	/**
	 * Returns the element's CP edit URL.
	 *
	 * @return string|false
	 */
	public function getCpEditUrl()
	{
		return UrlHelper::getCpUrl('sproutemail/campaigns/edit/' . $this->id);
	}

	public function getUrl()
	{
		if ($this->uri !== null)
		{
			$url = UrlHelper::getSiteUrl($this->uri, null, null, $this->locale);

			return $url;
		}
	}

	public function isReady()
	{
		return (bool) ($this->getStatus() == static::READY);
	}
}
