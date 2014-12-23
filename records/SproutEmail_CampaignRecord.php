<?php
namespace Craft;

/**
 * Class SproutEmail_CampaignRecord
 *
 * @package Craft
 * --
 * @property int    $id
 * @property string $name
 * @property string $handle
 * @property string $type
 * @property string $mailer
 * @property string $titleFormat
 * @property string $urlFormat
 * @property bool   $hasUrls
 * @property bool   $hasAdvancedTitles
 * @property string $template
 * @property string $templateCopyPaste
 * @property int    $fieldLayoutId
 */
class SproutEmail_CampaignRecord extends BaseRecord
{
	public $sectionRecord;

	/**
	 * Custom validation rules
	 *
	 * @var array
	 */
	public $rules = array();

	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'sproutemail_campaigns';
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'name'              => AttributeType::String,
			'handle'            => AttributeType::String,
			'type'              => array(
				AttributeType::Enum,
				'values' => array(
					Campaign::Email,
					Campaign::Notification
				)
			),
			'mailer'            => AttributeType::String,
			'titleFormat'       => AttributeType::String,
			'urlFormat'         => AttributeType::String,
			'hasUrls'           => array(
				AttributeType::Bool,
				'default' => true,
			),
			'hasAdvancedTitles' => array(
				AttributeType::Bool,
				'default' => true,
			),
			'template'          => AttributeType::String,
			'templateCopyPaste' => AttributeType::String,
			'dateCreated'       => AttributeType::DateTime,
			'dateUpdated'       => AttributeType::DateTime,
			// @related
			'fieldLayoutId'     => AttributeType::Number,
			'notifications'     => AttributeType::Mixed,
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'fieldLayout'   => array(
				static::BELONGS_TO,
				'FieldLayoutRecord',
				'onDelete' => static::SET_NULL
			),
			'entries'       => array(
				static::HAS_MANY,
				'SproutEmail_EntryRecord',
				'campaignId'
			),
			'notifications' => array(
				self::HAS_MANY,
				'SproutEmail_NotificationRecord',
				'campaignId'
			)
		);
	}

	/**
	 * @param array $rules
	 *
	 * @return void
	 */
	public function addRules($rules = array())
	{
		$this->rules [] = $rules;
	}

	/**
	 * Yii style validation rules;
	 * These are the 'base' rules but specific ones are added in the service based on
	 * the scenario
	 *
	 * @return array
	 */
	public function rules()
	{
		$rules = array(
			// required fields
			array(
				'name',
				'required'
			),
			// custom
			array(
				'mailer',
				'validMailer'
			)
		);

		return array_merge($rules, $this->rules);
	}

	/**
	 * Custom email provider validator
	 *
	 * @param string $attribute
	 *
	 * @return void
	 */
	public function validMailer($attribute)
	{
		if (!array_key_exists($this->$attribute, sproutEmail()->mailers->getMailers()))
		{
			$this->addError($attribute, 'Invalid email provider.');
		}
	}
}
