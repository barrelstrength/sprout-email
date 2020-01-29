<?php
/**
 * @link https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license https://craftcms.github.io/license
 */

namespace barrelstrength\sproutemail\migrations;

use barrelstrength\sproutbaseemail\emailtemplates\BasicTemplates;
use barrelstrength\sproutbaseemail\migrations\Install as SproutBaseNotificationInstall;
use barrelstrength\sproutbaseemail\models\Settings;
use Craft;
use craft\db\Migration;
use craft\services\Plugins;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\NotSupportedException;
use yii\web\ServerErrorHttpException;

class Install extends Migration
{
    private $sentEmailTable = '{{%sproutemail_sentemail}}';

    /**
     * @return bool|void
     * @throws ErrorException
     * @throws Exception
     * @throws NotSupportedException
     * @throws ServerErrorHttpException
     */
    public function safeUp()
    {
        $sentTable = $this->getDb()->tableExists($this->sentEmailTable);

        if ($sentTable == false) {
            $this->createTable($this->sentEmailTable,
                [
                    'id' => $this->primaryKey(),
                    'title' => $this->string(),
                    'emailSubject' => $this->string(),
                    'fromEmail' => $this->string(),
                    'fromName' => $this->string(),
                    'toEmail' => $this->string(),
                    'body' => $this->text(),
                    'htmlBody' => $this->text(),
                    'info' => $this->text(),
                    'status' => $this->string(),
                    'dateCreated' => $this->dateTime(),
                    'dateUpdated' => $this->dateTime(),
                    'uid' => $this->uid()
                ]
            );

            $this->addForeignKey(null, $this->sentEmailTable,
                ['id'], '{{%elements}}', ['id'], 'CASCADE');
        }

        $settings = new Settings();
        $basic = new BasicTemplates();

        $settings->emailTemplateId = get_class($basic);

        $pluginHandle = 'sprout-email';
        $projectConfig = Craft::$app->getProjectConfig();
        $projectConfig->set(Plugins::CONFIG_PLUGINS_KEY.'.'.$pluginHandle.'.settings', $settings->toArray());

        $this->runSproutBaseInstall();
    }

    public function safeDown()
    {

    }

    protected function runSproutBaseInstall()
    {
        $migration = new SproutBaseNotificationInstall();

        ob_start();
        $migration->safeUp();
        ob_end_clean();
    }
}