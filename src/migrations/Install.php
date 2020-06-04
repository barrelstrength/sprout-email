<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   https://craftcms.github.io/license
 */

namespace barrelstrength\sproutemail\migrations;

use barrelstrength\sproutbase\migrations\Install as SproutBaseInstall;
use barrelstrength\sproutbase\app\email\migrations\Install as SproutBaseEmailInstall;
use barrelstrength\sproutbase\app\fields\migrations\Install as SproutBaseFieldsInstall;
use barrelstrength\sproutbase\SproutBase;
use barrelstrength\sproutbase\app\sentemail\migrations\Install as SproutBaseSentEmailInstall;
use barrelstrength\sproutemail\SproutEmail;
use craft\db\Migration;
use Throwable;

class Install extends Migration
{
    /**
     * @return bool|void
     */
    public function safeUp()
    {
        SproutBase::$app->config->runInstallMigrations(SproutEmail::getInstance());

        return true;
    }

    /**
     * @return bool|void
     * @throws Throwable
     */
    public function safeDown()
    {
        SproutBase::$app->config->runUninstallMigrations(SproutEmail::getInstance());

        return true;
    }
}