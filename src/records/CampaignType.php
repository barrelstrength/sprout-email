<?php

namespace barrelstrength\sproutemail\records;

use craft\db\ActiveRecord;
use craft\records\FieldLayout;
use yii\db\ActiveQueryInterface;

/**
 * Class CampaignTypeRecord
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
class CampaignType extends ActiveRecord
{
    public $sectionRecord;

    /**
     * Custom validation rules
     *
     * @var array
     */
    public $rules = [];

    /**
     * @return string
     */
    public static function tableName(): string
    {
        return 'sproutemail_campaigntype';
    }

    /**
     * @param array $rules
     *
     * @return void
     */
    public function addRules(array $rules = [])
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
        $rules = [
            // required fields
            [
                'name',
                'required'
            ]
        ];

        return array_merge($rules, $this->rules);
    }

    public function getFieldLayout(): ActiveQueryInterface
    {
        return $this->hasOne(FieldLayout::class, ['id' => 'fieldLayoutId']);
    }
}
