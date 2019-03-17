<?php

namespace barrelstrength\sproutemail\migrations;

use barrelstrength\sproutbaseemail\emailtemplates\BasicTemplates;
use barrelstrength\sproutemail\models\Settings;
use craft\db\Migration;
use barrelstrength\sproutbaseemail\migrations\Install as SproutBaseNotificationInstall;
use Craft;
use craft\services\Plugins;

class Install extends Migration
{
    private $sentEmailTable = '{{%sproutemail_sentemail}}';

    /**
     * @return bool|void
     * @throws \yii\base\ErrorException
     * @throws \yii\base\Exception
     * @throws \yii\base\NotSupportedException
     * @throws \yii\web\ServerErrorHttpException
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
                ['id'], '{{%elements}}', ['id'], 'CASCADE', null);
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