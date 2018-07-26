<?php

namespace barrelstrength\sproutemail\records;

use craft\db\ActiveRecord;
use craft\records\Element;
use yii\db\ActiveQueryInterface;

/**
 * Class CampaignEmail
 *
 * @property $id                       bool
 * @property $subjectLine              string
 * @property $campaignTypeId           bool
 * @property $recipients               string
 * @property $emailSettings            string
 * @property $listSettings             string
 * @property $fromName                 string
 * @property $fromEmail                string
 * @property $replyToEmail             bool
 * @property $enableFileAttachments    bool
 * @property $dateScheduled            DateTime
 * @property $dateSent                 DateTime
 * @property $defaultBody              DateTime
 */
class CampaignEmail extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%sproutemail_campaignemails}}';
    }

    /**
     * Returns the entry’s element.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }
}
