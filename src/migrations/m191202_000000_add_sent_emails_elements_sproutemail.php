<?php /** @noinspection ClassConstantCanBeUsedInspection */

namespace barrelstrength\sproutemail\migrations;

use craft\db\Migration;
use barrelstrength\sproutbaseemail\migrations\m190212_000004_add_sent_emails_elements;
use Craft;

class m191202_000000_add_sent_emails_elements_sproutemail extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $migration = new m190212_000004_add_sent_emails_elements();

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
        echo "m191202_000000_add_sent_emails_elements_sproutemail cannot be reverted.\n";
        return false;
    }
}
