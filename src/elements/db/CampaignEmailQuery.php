<?php

namespace barrelstrength\sproutemail\elements\db;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class CampaignEmailQuery extends ElementQuery
{
    public $campaignTypeId;

    public $orderBy;

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        $this->joinElementTable('sproutemail_campaignemails');

        $this->query->select([
            'sproutemail_campaignemails.subjectLine',
            'sproutemail_campaignemails.campaignTypeId',
            'sproutemail_campaignemails.recipients',
            'sproutemail_campaignemails.emailSettings',
            'sproutemail_campaignemails.defaultBody',
            'sproutemail_campaignemails.listSettings',
            'sproutemail_campaignemails.fromName',
            'sproutemail_campaignemails.fromEmail',
            'sproutemail_campaignemails.replyToEmail',
            'sproutemail_campaignemails.enableFileAttachments',
            'sproutemail_campaignemails.dateScheduled',
            'sproutemail_campaignemails.dateSent',
            'sproutemail_campaignemails.dateCreated',
            'sproutemail_campaignemails.dateUpdated'
        ]);

        if ($this->campaignTypeId) {
            $this->subQuery->andWhere(Db::parseParam('sproutemail_campaignemails.campaignTypeId', $this->campaignTypeId));
        }

        if ($this->orderBy !== null && empty($this->orderBy)) {
            $this->orderBy = 'sproutemail_campaignemails.dateCreated desc';
        }

        return parent::beforePrepare();
    }
}