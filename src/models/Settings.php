<?php

namespace barrelstrength\sproutemail\models;

use barrelstrength\sproutbase\base\SproutSettingsInterface;
use Craft;
use craft\base\Model;

/**
 *
 * @property array $settingsNavItems
 */
class Settings extends Model implements SproutSettingsInterface
{
    /**
     * @var string
     */
    public $pluginNameOverride = '';
    /**
     * @var bool
     */
    public $appendTitleValue = false;
    /**
     * @var string
     */
    public $localeIdOverride = '';
    /**
     * @var bool
     */
    public $displayFieldHandles = false;
    /**
     * @var bool
     */
    public $enableCustomSections = false;
    /**
     * @var bool
     */
    public $enableMetaDetailsFields = false;
    /**
     * @var bool
     */
    public $enableMetadataRendering = true;
    /**
     * @var string
     */
    public $metadataVariable = 'metadata';
    /**
     * @var bool
     */
    public $enableNotificationEmails = true;
    /**
     * @var bool
     */
    public $enableCampaignEmails = false;
    /**
     * @var bool
     */
    public $enableSentEmails = false;
    /**
     * @var null
     */
    public $emailTemplateId;
    /**
     * @var int
     */
    public $enablePerEmailEmailTemplateIdOverride = 0;

    /**
     * @var int
     */
    public $sentEmailsLimit;

    /**
     * @inheritdoc
     */
    public function getSettingsNavItems(): array
    {
        return [
            'settingsHeading' => [
                'heading' => Craft::t('sprout-email', 'Settings'),
            ],
            'general' => [
                'label' => Craft::t('sprout-email', 'General'),
                'url' => 'sprout-email/settings/general',
                'selected' => 'general',
                'template' => 'sprout-base-email/settings/general'
            ],
            'mailers' => [
                'label' => Craft::t('sprout-email', 'Mailers'),
                'url' => 'sprout-email/settings/mailers',
                'selected' => 'mailers',
                'template' => 'sprout-base-email/settings/mailers'
            ],
            'emailHeading' => [
                'heading' => Craft::t('sprout-email', 'Email'),
            ],
//            'campaigntypes' => [
//                'label' => Craft::t('sprout-email', 'Campaigns'),
//                'url' => 'sprout-email/settings/campaigntypes',
//                'selected' => 'campaigntypes',
//                'template' => 'sprout-base-email/settings/campaigntypes',
//                'settingsForm' => false
//            ],
            'notifications' => [
                'label' => Craft::t('sprout-email', 'Notifications'),
                'url' => 'sprout-email/settings/notifications',
                'selected' => 'notifications',
                'template' => 'sprout-base-email/settings/notifications'
            ],
            'sentemails' => [
                'label' => Craft::t('sprout-email', 'Sent Emails'),
                'url' => 'sprout-email/settings/sentemails',
                'selected' => 'sentemails',
                'template' => 'sprout-base-email/settings/sentemails'
            ]
        ];
    }

    /**
     * Shared permissions they may be prefixed by another plugin. Before checking
     * these permissions the plugin name will be determined from the URL and appended.
     *
     * @example
     * /admin/sprout-reports/page => sproutReports-viewReports
     * /admin/sprout-forms/page => sproutForms-viewReports
     *
     * @return array
     */
    public static function getSharedPermissions(): array
    {
        return [
            'viewNotifications',
            'editNotifications'
        ];
    }
}