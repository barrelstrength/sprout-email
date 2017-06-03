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
 * @property datetime    $dateScheduled
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
	 * Ready     - Campaign is setup and is enabled
	 * Disabled  - Campaign should not be sendable or displayed to public
	 * Pending   - Campaign is setup but Entry is disabled
	 * Scheduled - Campaign has a scheduled date in the future
	 * Sent      - Campaign has a sent date in the past
	 *
	 */
	const READY     = 'ready';
	const DISABLED  = 'disabled';
	const PENDING   = 'pending';
	const SCHEDULED = 'scheduled';
	const SENT      = 'sent';

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
		$defaults = parent::defineAttributes();

		$attributes = array(
			'subjectLine'           => array(AttributeType::String, 'required' => true),
			'campaignTypeId'        => array(AttributeType::Number, 'required' => true),
			'recipients'            => array(AttributeType::String, 'required' => false),
			'fromName'              => array(AttributeType::String, 'minLength' => 2, 'maxLength' => 100, 'required' => false),
			'fromEmail'             => array(AttributeType::String, 'minLength' => 6, 'required' => false),
			'replyToEmail'          => array(AttributeType::String, 'required' => false),
			'enableFileAttachments' => array(AttributeType::Bool, 'default' => false),
			'dateScheduled'         => array(AttributeType::DateTime, 'default' => null),
			'dateSent'              => array(AttributeType::DateTime, 'default' => null),
			'template'              => array(AttributeType::String, 'default' => null),

			// @todo - integrate with Lists integration and delete old columns
			'listSettings'          => Attributetype::Mixed,
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
	 */

	/**
	 * Returns the entry status based on actual values and dynamic checking
	 *
	 * Disabled - Entry is disabled
	 * Pending  - Entry is enabled but some requirements are not yet met
	 * Ready    - Entry is enabled and all requirements are met
	 *
	 * @return string
	 */
	public function getStatus()
	{
		$status = parent::getStatus();

		if ($status == BaseElementModel::ENABLED)
		{
			$currentTime   = DateTimeHelper::currentTimeStamp();
			$dateScheduled = $this->dateScheduled->getTimestamp();

			if ($this->dateSent != null)
			{
				return static::SENT;
			}

			if ($this->dateScheduled != null && $dateScheduled > $currentTime && $this->dateSent == null)
			{
				return static::SCHEDULED;
			}

			return static::PENDING;
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

	/**
	 * Gets the Mailer associated with this Campaign Email
	 *
	 * @return SproutEmailBaseMailer|null
	 */
	public function getMailer()
	{
		$campaignType = sproutEmail()->campaignTypes->getCampaignTypeById($this->campaignTypeId);

		return $campaignType->getMailer();
	}

	/**
	 * Determine if this Campaign Email is able to be sent
	 *
	 * @return bool
	 */
	public function isReady()
	{
		return (bool) ($this->getMailer() && $this->isContentReady() && $this->isListReady());
	}

	/**
	 * Determine if the content of this email is ready to be sent
	 *
	 * @todo - is this necessary with proper validation? An entry shouldn't save if it has invalid fields...
	 *
	 * @return bool
	 */
	public function isContentReady()
	{
		$campaignType = sproutEmail()->campaignTypes->getCampaignTypeById($this->campaignTypeId);

		$params = array(
			'email'     => $this,
			'campaign'  => $campaignType,
			'recipient' => array(
				'firstName' => 'First',
				'lastName'  => 'Last',
				'email'     => 'user@domain.com'
			),

			// @deprecate - in favor of `email` in v3
			'entry'     => $this
		);

		$html = sproutEmail()->renderSiteTemplateIfExists($campaignType->template, $params);

		$text = sproutEmail()->renderSiteTemplateIfExists($campaignType->template . '.txt', $params);

		if ($html == null || $text == null)
		{
			return false;
		}

		return true;
	}

	/**
	 * Determine if this Campaign Email has lists that it will be sent to
	 *
	 * @return bool
	 */
	public function isListReady()
	{
		$mailer = $this->getMailer();

		if ($mailer->hasList)
		{
			if (empty($this->listSettings['listIds']))
			{
				return false;
			}
		}

		return true;
	}
}
