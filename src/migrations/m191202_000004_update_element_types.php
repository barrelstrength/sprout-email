<?php /** @noinspection ClassConstantCanBeUsedInspection */

namespace barrelstrength\sproutemail\migrations;

use craft\db\Migration;

class m191202_000004_update_element_types extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $campaignClasses = [
            0 => [
                'oldType' => 'barrelstrength\sproutbase\app\email\elements\CampaignEmail',
                'newType' => 'barrelstrength\sproutbaseemail\elements\CampaignEmail'
            ]
        ];

        foreach ($campaignClasses as $campaignClass) {
            $this->update('{{%elements}}', [
                'type' => $campaignClass['newType']
            ], ['type' => $campaignClass['oldType']], [], false);
        }

        $notificationClasses = [
            0 => [
                'oldType' => 'barrelstrength\sproutbase\app\email\elements\NotificationEmail',
                'newType' => 'barrelstrength\sproutbaseemail\elements\NotificationEmail'
            ]
        ];

        foreach ($notificationClasses as $notificationClass) {
            $this->update('{{%elements}}', [
                'type' => $notificationClass['newType']
            ], ['type' => $notificationClass['oldType']], [], false);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m180725_080640_update_element_types cannot be reverted.\n";
        return false;
    }
}
