<?php
/**
 * @link https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license https://craftcms.github.io/license
 */

namespace barrelstrength\sproutemail\migrations;

use barrelstrength\sproutbaseemail\migrations\m200110_000002_update_sendRule_column_to_text_type;
use craft\db\Migration;
use yii\base\NotSupportedException;

/**
 * m200110_000002_update_sendRule_column_to_text_type migration.
 */
class m200110_000002_update_sendRule_column_to_text_type_sproutemail extends Migration
{
    /**
     * @return bool
     * @throws NotSupportedException
     */
    public function safeUp(): bool
    {
        $migration = new m200110_000002_update_sendRule_column_to_text_type();

        ob_start();
        $migration->safeUp();
        ob_end_clean();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m200110_000002_update_sendRule_column_to_text_type cannot be reverted.\n";

        return false;
    }
}
