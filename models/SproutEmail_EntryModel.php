<?php
namespace Craft;

/**
 * Class SproutEmail_EntryModel
 *
 * @package Craft
 * --
 * @property int            $id
 * @property string         $subjectLine
 * @property int            $campaignId
 * @property string         $fromName
 * @property string         $fromEmail
 * @property string         $replyTo
 * @property bool           $sent
 * --
 * @property string|null    $uri
 * @property string         $slug
 * @property bool           $enabled
 */
class SproutEmail_EntryModel extends BaseElementModel
{
	protected $fields;
	protected $elementType = 'SproutEmail_Entry';

	/**
	 * @todo Clean up this status mess before 0.9.0
	 * Disabled - Campaign isn't even setup properly
	 * Pending -  Campaign is setup but Entry is disabled
	 * Ready -    Campaign is setup and is enabled
	 * Archived - has been sent, or exported and manually marked archived
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
		$recipientsString = $this->getAttribute('recipients');

		// Possibly called from entry edit screen
		if (is_null($element))
		{
			return $recipientsString;
		}

		// Previously converted to array somehow?
		if (is_array($recipientsString))
		{
			return $recipientsString;
		}

		// Previously stored as JSON string?
		if (stripos($recipientsString, '[') === 0)
		{
			return JsonHelper::decode($recipientsString);
		}

		// Still a string with possible twig generator code?
		if (stripos($recipientsString, '{') !== false)
		{
			try
			{
				$recipients = craft()->templates->renderObjectTemplate(
					$recipientsString,
					$element
				);

				return array_unique(ArrayHelper::filterEmptyStringsFromArray(ArrayHelper::stringToArray($recipients)));
			}
			catch (\Exception $e)
			{
				throw $e;
			}
		}

		// Just a regular CSV list
		if (!empty($recipientsString))
		{
			return ArrayHelper::filterEmptyStringsFromArray(ArrayHelper::stringToArray($recipientsString));
		}

		return array();
	}

	/**
	 * @return array
	 */
	protected function defineAttributes()
	{
		$defaults   = parent::defineAttributes();
		$attributes = array(
			'subjectLine'    => array(AttributeType::String, 'required' => true),
			'campaignId'     => array(AttributeType::Number, 'required' => true),
			'recipients'     => array(AttributeType::String, 'required' => false),
			'fromName'       => array(AttributeType::String, 'minLength' => 2, 'maxLength' => 100, 'required' => false),
			'fromEmail'      => array(AttributeType::String, 'minLength' => 6, 'required' => false),
			'replyTo'        => array(AttributeType::String, 'required' => false),
			'sent'           => AttributeType::Bool,
			// @related
			'recipientLists' => Attributetype::Mixed,
		);

		return array_merge($defaults, $attributes);
	}

	/**
	 * @return array
	 */
	public function rules()
	{
		$rules = parent::rules();

		$rules[] = array('replyTo', 'validateEmailWithOptionalPlaceholder');
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
					'attribute' => ($attribute == 'replyTo') ? 'Reply To' : 'From Email',
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
		$campaign = sproutEmail()->campaigns->getCampaignById($this->campaignId);

		return $campaign->getFieldLayout();
	}

	/**
	 * @param null $template
	 *
	 * @return null|string
	 */
	public function getUrlFormat($template = null)
	{
		$campaign = sproutEmail()->campaigns->getCampaignById($this->campaignId);

		if ($campaign && $campaign->hasUrls)
		{
			return $campaign->urlFormat;
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
		// @todo We can make this conditional statement more
		// advanced and check for the Service Provider and determine
		// specific things about each service provider to decide if an
		// email is ready or not.  For now, we'll just check to see if
		// it has a service provider and text template.

		$campaign = sproutEmail()->campaigns->getCampaignById($this->campaignId);

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

				if (empty($campaign->template) or empty($campaign->mailer))
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
	 * @return string Campaign Type
	 */
	public function getType()
	{
		$campaign = sproutEmail()->campaigns->getCampaignById($this->campaignId);

		return $campaign->type;
	}

	public function getUrl()
	{
		$cpTrigger = craft()->config->get('cpTrigger');

		$url = UrlHelper::getCpUrl($this->uri);

		return str_replace('/'.$cpTrigger, '', $url);
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
