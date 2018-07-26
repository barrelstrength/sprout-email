<?php

namespace barrelstrength\sproutemail\migrations;

use barrelstrength\sproutbase\app\email\migrations\m180515_000000_rename_notification_pluginId_column;
use barrelstrength\sproutbase\app\email\migrations\m180515_000001_update_notification_element_types;
use barrelstrength\sproutbase\app\email\migrations\m180515_000002_rename_notification_options_column;
use barrelstrength\sproutbase\app\email\migrations\m180515_000003_update_notification_eventId_types;
use barrelstrength\sproutbase\app\email\migrations\m180725_080639_add_notification_columns;
use barrelstrength\sproutbase\app\email\migrations\m180725_080640_update_element_types;

use craft\db\Migration;

/**
 * m180515_000000_schema_version_v302 migration.
 */
class m180515_000000_schema_version_v302 extends Migration
{
    /**
     * @return bool
     * @throws \yii\base\NotSupportedException
     */
    public function safeUp()
    {
        $notificationAddMigration = new m180725_080639_add_notification_columns();

        ob_start();
        $notificationAddMigration->safeUp();
        ob_end_clean();

        $notificationUpdateMigration = new m180725_080640_update_element_types();

        ob_start();
        $notificationUpdateMigration->safeUp();
        ob_end_clean();

        $notificationPluginIdMigration = new m180515_000000_rename_notification_pluginId_column();

        ob_start();
        $notificationPluginIdMigration->safeUp();
        ob_end_clean();

        
        $notificationOptionsMigration = new m180515_000002_rename_notification_options_column();

        ob_start();
        $notificationOptionsMigration->safeUp();
        ob_end_clean();

        $notificationElementTypeMigration = new m180515_000001_update_notification_element_types();

        ob_start();
        $notificationElementTypeMigration->safeUp();
        ob_end_clean();

        $notificationEmailTemplateIdMigration = new m180515_000003_update_notification_eventId_types();

        ob_start();
        $notificationEmailTemplateIdMigration->safeUp();
        ob_end_clean();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180515_000000_schema_version_v302 cannot be reverted.\n";
        return false;
    }
}
