<?php /** @noinspection ClassConstantCanBeUsedInspection */

namespace barrelstrength\sproutemail\migrations;

use barrelstrength\sproutbaseemail\migrations\m200219_000000_clean_up_cc_bcc_emailList_fields;
use craft\db\Migration;

class m200219_000000_clean_up_cc_bcc_emailList_fields_sproutemail extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $migration = new m200219_000000_clean_up_cc_bcc_emailList_fields();

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
        echo "m200219_000000_clean_up_cc_bcc_emailList_fields_sproutemail cannot be reverted.\n";

        return false;
    }
}
