<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   https://craftcms.github.io/license
 */

namespace barrelstrength\sproutemail;

use barrelstrength\sproutbase\base\SproutDependencyInterface;
use barrelstrength\sproutbase\base\SproutDependencyTrait;
use barrelstrength\sproutbase\SproutBase;
use barrelstrength\sproutbase\SproutBaseHelper;
use barrelstrength\sproutbaseemail\events\NotificationEmailEvent;
use barrelstrength\sproutbaseemail\models\Settings as SproutBaseEmailSettings;
use barrelstrength\sproutbaseemail\services\NotificationEmailEvents;
use barrelstrength\sproutbaseemail\SproutBaseEmailHelper;
use barrelstrength\sproutbasefields\SproutBaseFieldsHelper;
use barrelstrength\sproutbasereports\SproutBaseReportsHelper;
use barrelstrength\sproutbasesentemail\models\Settings as SentEmailSettingsModel;
use barrelstrength\sproutbasesentemail\SproutBaseSentEmail;
use barrelstrength\sproutbasesentemail\SproutBaseSentEmailHelper;
use barrelstrength\sproutemail\events\notificationevents\EntriesDelete;
use barrelstrength\sproutemail\events\notificationevents\EntriesSave;
use barrelstrength\sproutemail\events\notificationevents\Manual;
use barrelstrength\sproutemail\events\notificationevents\UsersActivate;
use barrelstrength\sproutemail\events\notificationevents\UsersDelete;
use barrelstrength\sproutemail\events\notificationevents\UsersSave;
use Craft;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\helpers\UrlHelper;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use yii\base\Event;

/**
 * @property array $cpNavItem
 * @property array $userPermissions
 * @property array $sproutDependencies
 * @property array $cpUrlRules
 */
class SproutEmail extends Plugin implements SproutDependencyInterface
{
    use SproutDependencyTrait;

    const EDITION_LITE = 'lite';
    const EDITION_PRO = 'pro';

    /**
     * @var bool
     */
    public $hasSettings = true;

    /**
     * @var bool
     */
    public $hasCpSection = true;

    /**
     * @var string
     */
    public $schemaVersion = '4.3.0';

    /**
     * @var string
     */
    public $minVersionRequired = '3.0.6';

    /**
     * @inheritdoc
     */
    public static function editions(): array
    {
        return [
            self::EDITION_LITE,
            self::EDITION_PRO,
        ];
    }

    public function init()
    {
        parent::init();

        SproutBaseHelper::registerModule();
        SproutBaseEmailHelper::registerModule();
        SproutBaseFieldsHelper::registerModule();
        SproutBaseSentEmailHelper::registerModule();
        SproutBaseReportsHelper::registerModule();

        Craft::setAlias('@sproutemail', $this->getBasePath());

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, $this->getCpUrlRules());
        });

        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function(RegisterUserPermissionsEvent $event) {
            $event->permissions['Sprout Email'] = $this->getUserPermissions();
        });

        Event::on(NotificationEmailEvents::class, NotificationEmailEvents::EVENT_REGISTER_EMAIL_EVENT_TYPES, static function(NotificationEmailEvent $event) {
            $event->events[] = EntriesSave::class;
            $event->events[] = EntriesDelete::class;
            $event->events[] = UsersSave::class;
            $event->events[] = UsersDelete::class;
            $event->events[] = UsersActivate::class;
            $event->events[] = Manual::class;
        });
    }

    /**
     * @return array
     */
    public function getCpNavItem(): array
    {
        $parent = parent::getCpNavItem();

        $sproutSentEmailIsEnabled = Craft::$app->getPlugins()->isPluginEnabled('sprout-sent-email');

        $sproutEmailSettings = $this->getSettings();
        $sentEmailSettings = SproutBaseSentEmail::$app->settings->getSentEmailSettings();

        // Allow user to override plugin name in sidebar
        if ($sproutEmailSettings->pluginNameOverride) {
            $parent['label'] = $sproutEmailSettings->pluginNameOverride;
        }

        $parent['url'] = 'sprout-email';

        $navigation = [];

        $sentEmailNavLabel = Craft::t('sprout-email', 'Sent Email');

        if ($sproutSentEmailIsEnabled && $sentEmailSettings->enableSentEmails) {
            SproutBase::$app->utilities->addSubNavIcon('sprout-email', $sentEmailNavLabel);
        }

        if (Craft::$app->getUser()->checkPermission('sproutEmail-viewSentEmail')) {
            if (!$sproutSentEmailIsEnabled || ($sproutSentEmailIsEnabled && $sentEmailSettings->enableSentEmails)) {
                $navigation['subnav']['sent-email'] = [
                    'label' => $sentEmailNavLabel,
                    'url' => $sproutSentEmailIsEnabled ? 'sprout-sent-email/sent-email' : 'sprout-email/sent-email',
                ];
            }
        }

        if (!$sproutEmailSettings->enableNotificationEmails && $sentEmailSettings->enableSentEmails) {
            $parent['url'] = 'sprout-email/sentemails';
        }

        if (Craft::$app->getUser()->checkPermission('sproutEmail-editNotifications') && $sproutEmailSettings->enableNotificationEmails) {
            $navigation['subnav']['notifications'] = [
                'label' => Craft::t('sprout-email', 'Notifications'),
                'url' => 'sprout-email/notifications'
            ];
        }

        if (Craft::$app->getUser()->getIsAdmin()) {
            $navigation['subnav']['settings'] = [
                'label' => Craft::t('sprout-email', 'Settings'),
                'url' => 'sprout-email/settings/general'
            ];
        }

        return array_merge($parent, $navigation);
    }

    /**
     * @return array
     */
    public function getUserPermissions(): array
    {
        return [
            'sproutEmail-viewSentEmail' => [
                'label' => Craft::t('sprout-email', 'View Sent Email'),
                'nested' => [
                    'sproutEmail-resendEmails' => [
                        'label' => Craft::t('sprout-email', 'Resend Sent Emails')
                    ]
                ]
            ],
            'sproutEmail-viewNotifications' => [
                'label' => Craft::t('sprout-email', 'View Notifications'),
                'nested' => [
                    'sproutEmail-editNotifications' => [
                        'label' => Craft::t('sprout-email', 'Edit Notification Emails')
                    ]
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function getSproutDependencies(): array
    {
        return [
            SproutDependencyInterface::SPROUT_BASE,
            SproutDependencyInterface::SPROUT_BASE_EMAIL,
            SproutDependencyInterface::SPROUT_BASE_FIELDS,
            SproutDependencyInterface::SPROUT_BASE_SENT_EMAIL,

            // Has dependency but relies on Sprout Reports Pro to install reports tables
            SproutDependencyInterface::SPROUT_BASE_REPORTS
        ];
    }

    /**
     * @return SproutBaseEmailSettings
     */
    protected function createSettingsModel(): SproutBaseEmailSettings
    {
        return new SproutBaseEmailSettings();
    }

    protected function afterInstall()
    {
        // Redirect to welcome page
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        Craft::$app->controller->redirect(UrlHelper::cpUrl('sprout-email/welcome'))->send();
    }

    private function getCpUrlRules(): array
    {
        return [
            '<pluginHandle:sprout-email>' => [
                'template' => 'sprout-base-email/index'
            ],
            '<pluginHandle:sprout-email>/index' => [
                'template' => 'sprout-base-email/index'
            ],

            // Notifications
            '<pluginHandle:sprout-email>/<pluginSection:notifications>' =>
                'sprout-base-email/notifications/notifications-index-template',
            '<pluginHandle:sprout-email>/<pluginSection:notifications>/edit/<emailId:\d+|new>' =>
                'sprout-base-email/notifications/edit-notification-email-template',
            '<pluginHandle:sprout-email>/<pluginSection:notifications>/settings/edit/<emailId:\d+|new>' =>
                'sprout-base-email/notifications/edit-notification-email-settings-template',

            // Preview
            '<pluginHandle:sprout-email>/<pluginSection:sent-email>/preview/<emailId:\d+>' => [
                'route' => 'sprout-base-sent-email/sent-email/preview'
            ],

            // Sent Emails
            '<pluginHandle:sprout-email>/<pluginSection:sent-email>' => [
                'route' => 'sprout-base-sent-email/sent-email/sent-email-index-template'
            ],

            // Campaigns
            '<pluginHandle:sprout-email>/<pluginSection:campaigns>/<campaignTypeId:\d+>/<emailId:new>' =>
                'sprout-email/campaign-email/edit-campaign-email',
            '<pluginHandle:sprout-email>/<pluginSection:campaigns>/edit/<emailId:\d+>' =>
                'sprout-email/campaign-email/edit-campaign-email',
            '<pluginHandle:sprout-email>/<pluginSection:campaigns>' => [
                'template' => 'sprout-base-email/campaigns/index'
            ],

            // Settings
            'sprout-email/settings/sent-email' => [
                'route' => 'sprout/settings/edit-settings',
                'params' => [
                    'sproutBaseSettingsType' => SentEmailSettingsModel::class,
                    'configFilename' => 'sprout-sent-email'
                ]
            ],
            'sprout-email/settings/<settingsSectionHandle:.*>' =>
                'sprout/settings/edit-settings',
            'sprout-email/settings' =>
                'sprout/settings/edit-settings'
        ];
    }
}
