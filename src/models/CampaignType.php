<?php

namespace barrelstrength\sproutemail\models;

use barrelstrength\sproutbase\app\email\base\Mailer;
use barrelstrength\sproutbase\SproutBase;
use barrelstrength\sproutemail\elements\CampaignEmail;
use craft\base\Field;
use craft\base\Model;
use craft\behaviors\FieldLayoutBehavior;
use craft\models\FieldLayout;
use craft\records\FieldLayoutField;
use craft\validators\UniqueValidator;

/**
 * Class CampaignTypeModel
 *
 * @mixin FieldLayoutBehavior
 * @package Craft
 * --
 * @property int                       $id
 * @property string                    $name
 * @property string                    $handle
 * @property string                    $mailer
 * @property string                    $titleFormat
 * @property string                    $urlFormat
 * @property bool                      $hasUrls
 * @property bool                      $hasAdvancedTitles
 * @property string                    $template
 * @property string                    $templateCopyPaste
 * @property int                       $fieldLayoutId
 * @property \craft\models\FieldLayout $fieldLayout
 * @property int                       $emailId
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

    public $emailTemplateId;
    /**
     * @var
     */
    protected $fields;

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            [['id'], 'number', 'integerOnly' => true],
            [['name', 'handle'], UniqueValidator::class, 'targetClass' => __CLASS__],
            [['name', 'handle'], 'required'],
            [['name', 'handle'], 'string', 'max' => 255],
        ];
    }

    /**
     * @return array
     */
    public function behaviors(): array
    {
        return [
            'fieldLayout' => [
                'class' => FieldLayoutBehavior::class,
                'elementType' => CampaignEmail::class
            ],
        ];
    }

    /**
     * @return FieldLayout
     * @throws \yii\base\InvalidConfigException
     */
    public function getFieldLayout(): FieldLayout
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
     * @return Field[]
     * @throws \yii\base\InvalidConfigException
     */
    public function getFields(): array
    {
        if ($this->fields !== null) {
            $this->fields = [];

            $fieldLayoutFields = $this->getFieldLayout()->getFields();

            /**
             * @var $fieldLayoutField FieldLayoutField
             */
            foreach ($fieldLayoutFields as $fieldLayoutField) {

                /**
                 * @var Field $field
                 */
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
     * @return Mailer
     * @throws \yii\base\Exception
     */
    public function getMailer(): Mailer
    {
        return SproutBase::$app->mailers->getMailerByName($this->mailer);
    }
}
