<?php

namespace barrelstrength\sproutemail\migrations;

use barrelstrength\sproutbase\app\email\migrations\m181128_000000_add_list_settings_column as baseMigration;
use craft\db\Migration;

/**
 * m181128_000000_add_list_settings_column migration.
 */
class m181128_000000_add_list_settings_column extends Migration
{
    /**
     * @return bool
     * @throws \yii\base\NotSupportedException
     */
    public function safeUp()
    {
        $notificationAddColumn = new baseMigration();

        ob_start();
        $notificationAddColumn->safeUp();
        ob_end_clean();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m181128_000000_add_list_settings_column cannot be reverted.\n";
        return false;
    }
}
