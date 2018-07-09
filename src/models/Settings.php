<?php

namespace barrelstrength\sproutemail\models;

use Craft;
use craft\base\Model;

class Settings extends Model
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
    public $emailTemplateId = null;
    /**
     * @var int
     */
    public $enablePerEmailEmailTemplateIdOverride = 0;

    /**
     * @return array
     */
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