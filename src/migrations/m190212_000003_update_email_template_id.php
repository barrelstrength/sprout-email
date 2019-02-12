<?php /** @noinspection ClassConstantCanBeUsedInspection */

namespace barrelstrength\sproutemail\migrations;

use craft\db\Migration;

/**
 * m190212_000003_update_email_template_id migration.
 */
class m190212_000003_update_email_template_id extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // This migration isn't relevant to most users, this was a minor change during beta development
        $types = [
            0 => [
                'oldType' => 'barrelstrength\sproutbase\app\email\emailtemplates\BasicTemplates',
                'newType' => 'barrelstrength\sproutbaseemail\emailtemplates\BasicTemplates'
            ],
            1 => [
                'oldType' => 'barrelstrength\sproutbase\app\email\emailtemplates\CustomTemplates',
                'newType' => 'barrelstrength\sproutbaseemail\emailtemplates\CustomTemplates'
            ]
        ];

        foreach ($types as $type) {
            $this->update('{{%sproutemail_notificationemails}}', [
                'emailTemplateId' => $type['newType']
            ], ['emailTemplateId' => $type['oldType']], [], false);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m190212_000003_update_email_template_id cannot be reverted.\n";
        return false;
    }
}
