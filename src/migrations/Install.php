<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   https://craftcms.github.io/license
 */

namespace barrelstrength\sproutemail\migrations;

use barrelstrength\sproutbase\base\SproutDependencyInterface;
use barrelstrength\sproutbase\migrations\Install as SproutBaseInstall;
use barrelstrength\sproutbaseemail\emailtemplates\BasicTemplates;
use barrelstrength\sproutbaseemail\migrations\Install as SproutBaseEmailInstall;
use barrelstrength\sproutbasesentemail\migrations\Install as SproutBaseSentEmailInstall;
use barrelstrength\sproutbaseemail\models\Settings;
use barrelstrength\sproutbasefields\migrations\Install as SproutBaseFieldsInstall;
use barrelstrength\sproutemail\SproutEmail;
use Craft;
use craft\db\Migration;
use craft\services\Plugins;
use Throwable;

class Install extends Migration
{
    /**
     * @return bool|void
     */
    public function safeUp()
    {
        $migration = new SproutBaseEmailInstall();
        ob_start();
        $migration->safeUp();
        ob_end_clean();

        $migration = new SproutBaseSentEmailInstall();
        ob_start();
        $migration->safeUp();
        ob_end_clean();
    }

    /**
     * @return bool|void
     * @throws Throwable
     */
    public function safeDown()
    {
        /** @var SproutEmail $plugin */
        $plugin = SproutEmail::getInstance();

        $sproutBaseEmailInUse = $plugin->dependencyInUse(SproutDependencyInterface::SPROUT_BASE_EMAIL);
        $sproutBaseFieldsInUse = $plugin->dependencyInUse(SproutDependencyInterface::SPROUT_BASE_FIELDS);
        $sproutBaseSentEmailInUse = $plugin->dependencyInUse(SproutDependencyInterface::SPROUT_BASE_SENT_EMAIL);
        $sproutBaseInUse = $plugin->dependencyInUse(SproutDependencyInterface::SPROUT_BASE);

        if (!$sproutBaseEmailInUse) {
            $migration = new SproutBaseEmailInstall();

            ob_start();
            $migration->safeDown();
            ob_end_clean();
        }

        if (!$sproutBaseFieldsInUse) {
            $migration = new SproutBaseFieldsInstall();

            ob_start();
            $migration->safeDown();
            ob_end_clean();
        }

        if (!$sproutBaseSentEmailInUse) {
            $migration = new SproutBaseSentEmailInstall();

            ob_start();
            $migration->safeDown();
            ob_end_clean();
        }

        if (!$sproutBaseInUse) {
            $migration = new SproutBaseInstall();

            ob_start();
            $migration->safeDown();
            ob_end_clean();
        }
    }
}