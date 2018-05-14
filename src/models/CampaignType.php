<?php

namespace barrelstrength\sproutemail\models;

use barrelstrength\sproutbase\SproutBase;
use barrelstrength\sproutemail\elements\CampaignEmail;
use craft\base\Model;
use craft\behaviors\FieldLayoutBehavior;
use craft\records\FieldLayoutField;
use craft\validators\UniqueValidator;

/**
 * Class CampaignTypeModel
 *
 * @mixin FieldLayoutBehavior
 * @package Craft
 * --
 * @property int    $id
 * @property string $name
 * @property string $handle
 * @property string $mailer
 * @property string $titleFormat
 * @property string $urlFormat
 * @property bool   $hasUrls
 * @property bool   $hasAdvancedTitles
 * @property string $template
 * @property string $templateCopyPaste
 * @property int    $fieldLayoutId
 * @property int    $emailId
 */
class CampaignType extends Model
{
    /**
     * @var
     */
    public $saveAsNew;
    /**
     * @var
     */
    public $id;
    /**
     * @var
     */
    public $name;
    /**
     * @var
     */
    public $handle;
    /**
     * @var
     */
    public $mailer;
    /**
     * @var
     */
    public $titleFormat;
    /**
     * @var
     */
    public $urlFormat;
    /**
     * @var
     */
    public $hasUrls;
    /**
     * @var
     */
    public $hasAdvancedTitles;
    /**
     * @var
     */
    public $template;
    /**
     * @var
     */
    public $templateCopyPaste;
    /**
     * @var
     */
    public $fieldLayoutId;
    /**
     * @var
     */
    public $emailId;

    /**
     * @var
     */
    protected $fields;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['id'], 'number', 'integerOnly' => true],
            [['name', 'handle'], UniqueValidator::class, 'targetClass' => CampaignType::class],
            [['name', 'handle'], 'required'],
            [['name', 'handle'], 'string', 'max' => 255],
        ];
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            'fieldLayout' => [
                'class' => FieldLayoutBehavior::class,
                'elementType' => CampaignEmail::class
            ],
        ];
    }

    /**
     * @return \craft\models\FieldLayout
     * @throws \yii\base\InvalidConfigException
     */
    public function getFieldLayout()
    {
        /**
         * @var $behavior FieldLayoutBehavior
         */
        $behavior = $this->getBehavior('fieldLayout');

        return $behavior->getFieldLayout();
    }

    /**
     * Returns the fields associated with this form.
     *
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public function getFields()
    {
        if ($this->fields !== null) {
            $this->fields = [];

            $fieldLayoutFields = $this->getFieldLayout()->getFields();

            /**
             * @var $fieldLayoutField FieldLayoutField
             */
            foreach ($fieldLayoutFields as $fieldLayoutField) {
                $field = $fieldLayoutField->getField();
                $field->required = $fieldLayoutField->required;
                $this->fields[] = $field;
            }
        }

        return $this->fields;
    }

    /**
     * Sets the fields associated with this form.
     *
     * @param $fields
     */
    public function setFields($fields)
    {
        $this->fields = $fields;
    }

    /**
     * @return \barrelstrength\sproutbase\app\email\base\Mailer|null
     */
    public function getMailer()
    {
        return SproutBase::$app->mailers->getMailerByName($this->mailer);
    }
}
