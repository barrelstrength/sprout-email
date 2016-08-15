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
class SproutEmail_CampaignTypeRecord extends BaseRecord
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
		return 'sproutemail_campaigntype';
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'name'              => AttributeType::String,
			'handle'            => AttributeType::String,
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
			'fieldLayoutId'     => AttributeType::Number
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'fieldLayout'    => array(
				static::BELONGS_TO,
				'FieldLayoutRecord',
				'onDelete' => static::SET_NULL
			),
			'campaignEmails' => array(
				static::HAS_MANY,
				'SproutEmail_CampaignEmailRecord',
				'campaignTypeId'
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
		if (!($mailers = sproutEmail()->mailers->getInstalledMailers()) || !array_key_exists($this->$attribute, $mailers))
		{
			$this->addError($attribute, Craft::t('Invalid email provider.'));
		}
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
		$i        = 1;
		$band     = true;
		do
		{
			$newField = $value . $i;
			$campaign = sproutEmail()->campaignTypes->getFieldValue($field, $newField);
			if (is_null($campaign))
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
		// Validate the name and handle fields when the record is save as new
		if (isset($_POST["saveAsNew"]))
		{
			if ($_POST['saveAsNew'])
			{
				if (sproutEmail()->campaignTypes->getFieldValue('name', $this->name))
				{
					$this->name = $this->getFieldAsNew('name', $this->name);
				}

				if (sproutEmail()->campaignTypes->getFieldValue('handle', $this->handle))
				{
					$this->handle = $this->getFieldAsNew('handle', $this->handle);
				}
			}
		}

		return true;
	}
}
