<?php /** @noinspection ClassConstantCanBeUsedInspection */

namespace barrelstrength\sproutemail\migrations;

use barrelstrength\sproutbaseemail\migrations\m200219_000000_clean_up_cc_bcc_emailList_fields;
use barrelstrength\sproutbasesentemail\migrations\m200411_000000_update_sent_email_element_type_class;
use craft\db\Migration;

class m200411_000000_update_sent_email_element_type_class_sproutemail extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $migration = new m200411_000000_update_sent_email_element_type_class();

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
        echo "m200411_000000_update_sent_email_element_type_class_sproutemail cannot be reverted.\n";

        return false;
    }
}
