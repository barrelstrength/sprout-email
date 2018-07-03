<?php

namespace barrelstrength\sproutemail\migrations;

use barrelstrength\sproutbase\app\email\emailtemplates\BasicTemplates;
use barrelstrength\sproutemail\models\Settings;
use craft\db\Migration;
use barrelstrength\sproutbase\app\email\migrations\Install as SproutBaseNotificationInstall;

class Install extends Migration
{
    private $campaignEmailTable = '{{%sproutemail_campaignemails}}';
    private $campaignTypeTable = '{{%sproutemail_campaigntype}}';
    private $sentEmailTable = '{{%sproutemail_sentemail}}';

    /**
     * @return bool|void
     * @throws \ReflectionException
     * @throws \yii\db\Exception
     */
    public function safeUp()
    {
        $this->createTable($this->campaignEmailTable,
            [
                'id' => $this->primaryKey(),
                'subjectLine' => $this->string()->notNull(),
                'campaignTypeId' => $this->integer()->notNull(),
                'recipients' => $this->text(),
                'emailSettings' => $this->text(),
                'defaultBody' => $this->text(),
                'listSettings' => $this->text(),
                'fromName' => $this->string(),
                'fromEmail' => $this->string(),
                'replyToEmail' => $this->string(),
                'enableFileAttachments' => $this->boolean(),
                'dateScheduled' => $this->dateTime(),
                'dateSent' => $this->dateTime(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid()
            ]
        );

        $this->addForeignKey(null, $this->campaignEmailTable, ['id'], '{{%elements}}', ['id'], 'CASCADE', null);

        $this->createTable($this->campaignTypeTable,
            [
                'id' => $this->primaryKey(),
                'name' => $this->string()->notNull(),
                'handle' => $this->string()->notNull(),
                'type' => $this->string()->notNull(),
                'mailer' => $this->string()->notNull(),
                'emailTemplateId' => $this->string(),
                'titleFormat' => $this->string(),
                'urlFormat' => $this->string(),
                'hasUrls' => $this->boolean(),
                'hasAdvancedTitles' => $this->boolean(),
                'template' => $this->string(),
                'templateCopyPaste' => $this->string(),
                'fieldLayoutId' => $this->integer(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid()
            ]
        );

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
        }

        $settings = new Settings();
        $basic = new BasicTemplates();
        $settings->emailTemplateId = $basic->getTemplateId();

        $newSettings = json_encode($settings->getAttributes());

        $this->db->createCommand()->update('{{%plugins}}', [
            'settings' => $newSettings
        ], [
            'handle' => strtolower('sprout-email')
        ])->execute();

        $this->runSproutBaseInstall();
    }

    public function safeDown()
    {
        $this->dropTable($this->campaignEmailTable);
        $this->dropTable($this->campaignTypeTable);
    }

    protected function runSproutBaseInstall()
    {
        $migration = new SproutBaseNotificationInstall();

        ob_start();
        $migration->safeUp();
        ob_end_clean();
    }
}