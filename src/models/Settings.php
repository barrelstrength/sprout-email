<?php

namespace barrelstrength\sproutemail\models;

use Craft;
use craft\base\Model;

class Settings extends Model
{
    public $pluginNameOverride = '';
    public $appendTitleValue = false;
    public $localeIdOverride = '';
    public $displayFieldHandles = false;
    public $enableCustomSections = false;
    public $enableMetaDetailsFields = false;
    public $enableMetadataRendering = true;
    public $metadataVariable = 'metadata';
    public $enableNotificationEmails = true;
    public $enableCampaignEmails = false;
    public $enableSentEmails = false;
    public $emailTemplateId = '';
    public $enablePerEmailEmailTemplateIdOverride = 0;

    public function getSettingsNavItems()
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
            'emailHeading' => [
                'heading' => Craft::t('sprout-email', 'Email'),
            ],
            'campaigntypes' => [
                'label' => Craft::t('sprout-email', 'Campaign Types'),
                'url' => 'sprout-email/settings/campaigntypes',
                'selected' => 'campaigntypes',
                'template' => 'sprout-base-email/settings/campaigntypes',
                'settingsForm' => false
            ],
            'mailers' => [
                'label' => Craft::t('sprout-email', 'Mailers'),
                'url' => 'sprout-email/settings/mailers',
                'selected' => 'mailers',
                'template' => 'sprout-base-email/settings/mailers'
            ],
            'integrationsHeading' => [
                'heading' => Craft::t('sprout-email', 'Integrations'),
            ],
            'sproutseo' => [
                'label' => Craft::t('sprout-email', 'SEO'),
                'url' => 'sprout-email/settings/sproutseo',
                'selected' => 'sproutseo',
                'template' => 'sprout-base-email/settings/seo'
            ],
        ];
    }
}