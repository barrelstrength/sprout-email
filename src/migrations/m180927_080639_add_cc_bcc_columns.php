<?php

namespace barrelstrength\sproutemail\migrations;

use barrelstrength\sproutbaseemail\migrations\m180927_080639_add_cc_bcc_columns as baseMigration;
use craft\db\Migration;

/**
 * m180927_080639_add_cc_bcc_columns migration.
 */
class m180927_080639_add_cc_bcc_columns extends Migration
{
    /**
     * @return bool
     * @throws \yii\base\NotSupportedException
     */
    public function safeUp(): bool
    {
        $notificationAddColumns = new baseMigration();

        ob_start();
        $notificationAddColumns->safeUp();
        ob_end_clean();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m180927_080639_add_cc_bcc_columns cannot be reverted.\n";
        return false;
    }
}
