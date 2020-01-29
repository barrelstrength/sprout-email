<?php /** @noinspection ClassConstantCanBeUsedInspection */

namespace barrelstrength\sproutemail\migrations;

use barrelstrength\sproutbaseemail\migrations\m190212_000003_update_email_template_id;
use craft\db\Migration;

/**
 * m190212_000003_update_email_template_id migration.
 */
class m190212_000003_update_email_template_id_sproutemail extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $migration = new m190212_000003_update_email_template_id();

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
        echo "m190212_000003_update_email_template_id_sproutemail cannot be reverted.\n";

        return false;
    }
}
