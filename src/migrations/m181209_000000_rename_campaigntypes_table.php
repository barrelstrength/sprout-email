<?php
/**
 * @link https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license https://craftcms.github.io/license
 */

namespace barrelstrength\sproutemail\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\MigrationHelper;

/**
 * m181209_000000_rename_campaigntypes_table migration.
 */
class m181209_000000_rename_campaigntypes_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $oldCampaignTypeTable = 'sproutemail_campaigntype';
        $newCampaignTypeTable = 'sproutemail_campaigntypes';

        if (!Craft::$app->db->tableExists($newCampaignTypeTable) && Craft::$app->db->tableExists($oldCampaignTypeTable)) {
            MigrationHelper::renameTable($oldCampaignTypeTable, $newCampaignTypeTable);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m181209_000000_rename_campaigntypes_table cannot be reverted.\n";

        return false;
    }
}
