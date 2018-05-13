<?php

namespace barrelstrength\sproutemail\elements\db;

use craft\elements\db\ElementQuery;

class SentEmailQuery extends ElementQuery
{
    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        $this->joinElementTable('sproutemail_sentemail');
        $this->query->select([
            'sproutemail_sentemail.title',
            'sproutemail_sentemail.emailSubject',
            'sproutemail_sentemail.fromEmail',
            'sproutemail_sentemail.fromName',
            'sproutemail_sentemail.toEmail',
            'sproutemail_sentemail.body',
            'sproutemail_sentemail.htmlBody',
            'sproutemail_sentemail.info',
            'sproutemail_sentemail.status',
            'sproutemail_sentemail.dateCreated',
            'sproutemail_sentemail.dateUpdated'
        ]);

        return parent::beforePrepare();
    }
}