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
 * @property string $replyToEmail
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
			'subjectLine'           => array(AttributeType::String, 'required' => true),
			'campaignId'            => array(AttributeType::Number, 'required' => true),
			'recipients'            => array(AttributeType::String, 'required' => false),
			'fromName'              => array(AttributeType::String, 'minLength' => 2, 'maxLength' => 100, 'required' => true),
			'fromEmail'             => array(AttributeType::Email, 'required' => true),
			'replyToEmail'          => array(AttributeType::Email, 'required' => true),
			'sent'                  => AttributeType::Bool,
			'enableFileAttachments' => array(AttributeType::Bool, 'default' => false),
		);
	}

	/**
	 * @return array
	 */
	public function rules()
	{
		$rules = parent::rules();

		$rules[] = array('replyToEmail', 'validateEmailWithOptionalPlaceholder');
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
					'attribute' => ($attribute == 'replyToEmail') ? Craft::t('Reply To') : Craft::t('From Email'),
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

	/**
	 * Create a secuencial string for the "name" and "handle" fields if they are already taken
	 *
	 * @param string
	 * @param string
	 * return string
	 */
	private function getFieldAsNew($field, $value)
	{
		$newField = null;
		$i = 1;
		$band = true;
		do
		{
			$newField = $value . $i;
			$entry = sproutEmail()->entries->getFieldValue($field, $newField);
			if (is_null($entry))
			{
				$band = false;
			}

			$i++;
		}
		while ($band);

		return $newField;
	}

	/**
	 * Before Validate
	 *
	 */
	protected function beforeValidate()
	{
		// Validate the subjectLine field when the record is save as new
		if (isset($_POST["saveAsNew"]))
		{
			if ($_POST['saveAsNew'])
			{
				if (sproutEmail()->entries->getFieldValue('subjectLine', $this->subjectLine))
				{
					$this->subjectLine = $this->getFieldAsNew('subjectLine', $this->subjectLine);
				}
			}
		}

		return true;
	}
}
