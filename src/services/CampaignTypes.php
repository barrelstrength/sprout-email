<?php

namespace barrelstrength\sproutemail\services;

use barrelstrength\sproutemail\elements\CampaignEmail;
use barrelstrength\sproutemail\models\CampaignType;
use barrelstrength\sproutemail\records\CampaignType as CampaignTypeRecord;
use craft\base\Component;
use Craft;
use craft\queue\jobs\ResaveElements;
use yii\base\Exception;

/**
 * Class CampaignTypes
 *
 * @package barrelstrength\sproutemail\services
 *
 * @property array $campaignTypes
 */
class CampaignTypes extends Component
{
    /**
     * @return array
     */
    public function getCampaignTypes()
    {
        $campaignsTypes = CampaignTypeRecord::find()->all();

        $models = [];

        if ($campaignsTypes) {
            foreach ($campaignsTypes as $campaignsType) {
                $campaignTypeModel = new CampaignType();

                $campaignTypeModel->setAttributes($campaignsType->getAttributes(), false);

                $models[] = $campaignTypeModel;
            }
        }

        return $models;
    }

    public function getCampaignTypeById($campaignTypeId)
    {
        $campaignRecord = CampaignTypeRecord::findOne($campaignTypeId);

        $model = new CampaignType();

        if ($campaignRecord) {
            $attributes = $campaignRecord->getAttributes();

            $model->setAttributes($attributes, false);
        }

        return $model;
    }

    /**
     * @param CampaignType $campaignType
     *
     * @return CampaignType|bool
     * @throws \Exception
     * @throws \craft\errors\SiteNotFoundException
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
     */
    public function saveCampaignType(CampaignType $campaignType)
    {
        $isNew = !$campaignType->id;

        $campaignTypeRecord = new CampaignTypeRecord();
        $oldCampaignType = null;

        if (is_numeric($campaignType->id) && !$campaignType->saveAsNew) {
            $campaignTypeRecord = CampaignTypeRecord::findOne($campaignType->id);

            $attributes = $campaignTypeRecord->getAttributes();

            $campaignTypeModel = new CampaignType();

            $oldCampaignType = $campaignTypeModel->setAttributes($attributes, false);
        }

        $db = Craft::$app->getDb();

        $transaction = $db->beginTransaction();

        $fieldLayout = $campaignType->getFieldLayout();

        Craft::$app->getFields()->saveLayout($fieldLayout);

        // Delete our previous record
        if ($campaignType->id && $oldCampaignType && $oldCampaignType->fieldLayoutId) {
            Craft::$app->getFields()->deleteLayoutById($oldCampaignType->fieldLayoutId);
        }

        // Assign our new layout id info to our
        // form model and records
        $campaignType->fieldLayoutId = $fieldLayout->id;
        $campaignTypeRecord->fieldLayoutId = $fieldLayout->id;

        $campaignTypeRecord = $this->saveCampaignTypeInfo($campaignType, $campaignTypeRecord);

        if ($campaignTypeRecord->hasErrors()) {
            if ($transaction) {
                $transaction->rollBack();
            }

            return false;
        }

        if (!$isNew) {
            $siteId = Craft::$app->getSites()->getPrimarySite()->id;

            Craft::$app->getQueue()->push(new ResaveElements([
                'description' => Craft::t('sprout-email', 'Resaving campaign emails'),
                'elementType' => CampaignEmail::class,
                'criteria' => [
                    'siteId' => $siteId,
                    'campaignTypeId' => $campaignType->id,
                    'status' => null,
                    'enabledForSite' => false,
                    'limit' => null,
                ]
            ]));
        }

        if ($transaction) {
            $transaction->commit();

            // Pass update attributes
            $campaignType->setAttributes($campaignTypeRecord->getAttributes(), false);
        }

        return $campaignType;
    }

    /**
     * @param CampaignType       $campaignType
     * @param CampaignTypeRecord $campaignTypeRecord
     *
     * @return CampaignTypeRecord
     * @throws \Exception
     */
    protected function saveCampaignTypeInfo(CampaignType $campaignType, CampaignTypeRecord $campaignTypeRecord)
    {
        if ($campaignType->id !== null &&
            is_numeric($campaignType->id) &&
            !$campaignType->saveAsNew &&
            !$campaignTypeRecord) {

            throw new Exception(Craft::t('sprout-email', 'No campaign exists with the ID “{id}”', [
                'id' => $campaignType->id
            ]));
        }

        if (!$campaignType->hasAdvancedTitles) {
            $campaignType->titleFormat = null;
        }

        // Set common attributes
        $campaignTypeRecord->fieldLayoutId = $campaignType->fieldLayoutId;
        $campaignTypeRecord->name = $campaignType->name;
        $campaignTypeRecord->handle = $campaignType->handle;
        $campaignTypeRecord->titleFormat = $campaignType->titleFormat;
        $campaignTypeRecord->hasUrls = $campaignType->hasUrls;
        $campaignTypeRecord->hasAdvancedTitles = $campaignType->hasAdvancedTitles;
        $campaignTypeRecord->mailer = $campaignType->mailer;
        $campaignTypeRecord->emailTemplateId = $campaignType->emailTemplateId;

        $campaignTypeRecord->urlFormat = $campaignType->urlFormat;
        $campaignTypeRecord->template = $campaignType->template;
        $campaignTypeRecord->templateCopyPaste = $campaignType->templateCopyPaste;

        if ($campaignType->saveAsNew) {
            $campaignTypeRecord->handle = $campaignType->handle.'-1';
        }

        $campaignTypeRecord->validate();
        $campaignType->addErrors($campaignTypeRecord->getErrors());

        // Get the title back from model because record validate append it with 1
        if ($campaignType->saveAsNew) {
            $campaignTypeRecord->name = $campaignType->name;
        }

        if (!$campaignTypeRecord->hasErrors()) {
            $campaignTypeRecord->save(false);
        }

        return $campaignTypeRecord;
    }

    /**
     * Deletes a campaign by its ID along with associations;
     * also cleans up any remaining orphans
     *
     * @param int $campaignTypeId
     *
     * @return bool
     */
    public function deleteCampaignType($campaignTypeId)
    {
        try {
            Craft::$app->getDb()->createCommand()
                ->delete(
                    'sproutemail_campaigntype',
                    [
                        'id' => $campaignTypeId
                    ])
                ->execute();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
