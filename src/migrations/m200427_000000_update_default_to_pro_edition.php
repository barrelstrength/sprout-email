<?php /**
 * @link      https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   https://craftcms.github.io/license
 */

namespace barrelstrength\sproutemail\migrations;

use barrelstrength\sproutemail\SproutEmail;
use Craft;
use craft\db\Migration;
use Throwable;

class m200427_000000_update_default_to_pro_edition extends Migration
{
    /**
     * @return bool
     * @throws Throwable
     */
    public function safeUp(): bool
    {
        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('plugins.sprout-email.schemaVersion', true);
        if (version_compare($schemaVersion, '4.3.0', '>=')) {
            return true;
        }

        Craft::$app->getPlugins()->switchEdition('sprout-email', SproutEmail::EDITION_PRO);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m200427_000000_update_default_to_pro_edition cannot be reverted.\n";

        return false;
    }
}
