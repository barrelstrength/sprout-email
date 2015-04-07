<?php
namespace Craft;

/**
 * Class SproutEmail_EntryRecord
 *
 * @package Craft
 * --
 * @property int    $id
 * @property int    $campaignId
 * @property int    $recipientListId
 * @property string $subjectLine
 * @property string $fromName
 * @property string $fromEmail
 * @property string $replyTo
 * @property bool   $sent
 */
class SproutEmail_EntryRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'sproutemail_campaigns_entries';
	}

	/**
	 * @todo Device a way to store sender info as a relationship if it makes more sense
	 *
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'subjectLine' => array(AttributeType::String, 'required' => true),
			'campaignId'  => array(AttributeType::Number, 'required' => true),
			'recipients'  => array(AttributeType::Mixed, 'required' => false),
			'fromName'    => array(AttributeType::String, 'required' => false, 'minLength' => 2, 'maxLength' => 100),
			'fromEmail'   => array(AttributeType::String, 'required' => false, 'minLength' => 6),
			'replyTo'     => array(AttributeType::String, 'required' => false),
			'sent'        => AttributeType::Bool,
		);
	}

	/**
	 * @return array
	 */
	public function rules()
	{
		$rules = parent::rules();

		$rules[] = array('replyTo', 'validateEmailWithOptionalPlaceholder');
		$rules[] = array('fromEmail', 'validateEmailWithOptionalPlaceholder');
		$rules[] = array('recipients', 'validateOnTheFlyRecipients');

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

		// Validate only if it is not a placeholder and it is not empty
		if (strpos($value, '{') !== 0 && !empty($this->{$attribute}))
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

	/**
	 * Ensures that all email addresses in recipients are valid
	 *
	 * @param $attribute
	 */
	public function validateOnTheFlyRecipients($attribute)
	{
		$value = $this->{$attribute};

		if (is_array($value) && count($value))
		{
			foreach ($value as $recipient)
			{
				if (strpos($recipient, '{') !== 0 && !empty($this->{$attribute}))
				{
					if (!filter_var($recipient, FILTER_VALIDATE_EMAIL))
					{
						$params = array(
							'attribute' => $attribute,
						);

						$this->addError($attribute, Craft::t('All recipients must be placeholders or valid email addresses.', $params));
					}
				}
			}
		}
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'element'        => array(
				static::BELONGS_TO,
				'ElementRecord',
				'id',
				'required' => true,
				'onDelete' => static::CASCADE
			),
			'campaign'       => array(
				static::BELONGS_TO,
				'SproutEmail_CampaignRecord',
				'required' => true,
				'onDelete' => static::CASCADE
			),
			'recipientLists' => array(
				static::HAS_MANY,
				'SproutEmail_EntryRecipientListRecord',
				'entryId'
			)
		);
	}
}
