<?php /** @noinspection ClassConstantCanBeUsedInspection */

namespace barrelstrength\sproutemail\migrations;

use barrelstrength\sproutbasejobs\migrations\Install as SproutBaseJobsInstallMigration;
use craft\db\Migration;

class m190706_000000_sproutjobs_install_migration_sproutemail extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $migration = new SproutBaseJobsInstallMigration();

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
        echo "m190706_000000_sproutjobs_install_migration_sproutemail cannot be reverted.\n";
        return false;
    }
}
