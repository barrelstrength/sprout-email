<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   https://craftcms.github.io/license
 */

namespace barrelstrength\sproutemail\migrations;

use craft\db\Migration;

/**
 * m181113_000000_update_sent_email_element_types migration.
 */
class m181113_000000_update_sent_email_element_types extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $types = [
            0 => [
                'oldType' => 'SproutEmail_SentEmail',
                'newType' => 'barrelstrength\sproutemail\elements\SentEmail'
            ]
        ];

        foreach ($types as $type) {
            $this->update('{{%elements}}', [
                'type' => $type['newType']
            ], ['type' => $type['oldType']], [], false);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m181113_000000_update_sent_email_element_types cannot be reverted.\n";

        return false;
    }
}
