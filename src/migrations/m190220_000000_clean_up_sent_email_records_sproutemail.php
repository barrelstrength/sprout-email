<?php /**
 * @link      https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   https://craftcms.github.io/license
 */

namespace barrelstrength\sproutemail\migrations;

use barrelstrength\sproutbaseemail\migrations\m190220_000000_clean_up_sent_email_records as BaseMigration;
use craft\db\Migration;
use yii\db\Exception;

class m190220_000000_clean_up_sent_email_records_sproutemail extends Migration
{
    /**
     * @return bool
     * @throws Exception
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
        echo "m190220_000000_clean_up_sent_email_records_sproutemail cannot be reverted.\n";

        return false;
    }
}
