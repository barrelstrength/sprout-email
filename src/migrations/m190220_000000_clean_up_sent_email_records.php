<?php /** @noinspection ClassConstantCanBeUsedInspection */

namespace barrelstrength\sproutemail\migrations;

use craft\db\Migration;
use barrelstrength\sproutbaseemail\migrations\m190220_000000_clean_up_sent_email_records as BaseMigration;

class m190220_000000_clean_up_sent_email_records extends Migration
{
    /**
     * @return bool
     * @throws \yii\db\Exception
     */
    public function safeUp(): bool
    {
        $migration = new BaseMigration();

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
        echo "m190220_000000_clean_up_sent_email_records cannot be reverted.\n";
        return false;
    }
}
