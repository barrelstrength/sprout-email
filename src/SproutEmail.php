<?php
/**
 * @link https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license https://craftcms.github.io/license
 */

namespace barrelstrength\sproutemail;

use barrelstrength\sproutbase\SproutBase;
use barrelstrength\sproutbase\SproutBaseHelper;
use barrelstrength\sproutbaseemail\events\NotificationEmailEvent;
use barrelstrength\sproutbaseemail\models\Settings;
use barrelstrength\sproutbaseemail\services\NotificationEmailEvents;
use barrelstrength\sproutbaseemail\SproutBaseEmailHelper;
use barrelstrength\sproutbasefields\SproutBaseFieldsHelper;
use barrelstrength\sproutemail\events\notificationevents\EntriesDelete;
use barrelstrength\sproutemail\events\notificationevents\EntriesSave;
use barrelstrength\sproutemail\events\notificationevents\Manual;
use barrelstrength\sproutemail\events\notificationevents\UsersActivate;
use barrelstrength\sproutemail\events\notificationevents\UsersDelete;
use barrelstrength\sproutemail\events\notificationevents\UsersSave;
use barrelstrength\sproutemail\services\App;
use Craft;
use craft\base\Plugin;
use craft\events\RegisterCpNavItemsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\services\UserPermissions;
use craft\web\twig\variables\Cp;
use craft\web\UrlManager;
use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\mail\BaseMailer;
use yii\mail\MailEvent;

/**
 * @property array $cpNavItem
 * @property array $userPermissions
 * @property array $cpUrlRules
 */
class SproutEmail extends Plugin
{

    /**
     * Enable use of SproutEmail::$plugin-> in place of Craft::$app->
     *
     * @var App
     */
    public static $app;


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
    public $schemaVersion = '4.1.4';

    /**
     * @var string
     */
    public $minVersionRequired = '3.0.6';

    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();

        SproutBaseHelper::registerModule();
        SproutBaseEmailHelper::registerModule();
        SproutBaseFieldsHelper::registerModule();

        $this->setComponents([
            'app' => App::class
        ]);

        self::$app = $this->get('app');

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
            ],

            // Reports
//            'sproutEmail-viewReports' => [
//                'label' => Craft::t('sprout-email', 'View Reports'),
//                'nested' => [
//                    'sproutEmail-editReports' => [
//                        'label' => Craft::t('sprout-email', 'Edit Reports')
//                    ]
//                ]
//            ]
        ];
    }

    /**
     * @return SproutBaseEmailSettings
     */
    protected function createSettingsModel(): SproutBaseEmailSettings
    {
        return new SproutBaseEmailSettings();
    }

    private function getCpUrlRules(): array
    {
        return [
            'sprout-email' => [
                'template' => 'sprout-base-email/index'
            ],
            'sprout-email/index' => [
                'template' => 'sprout-base-email/index'
            ],

            // Notifications
            'sprout-email/notifications' =>
                'sprout-base-email/notifications/notifications-index-template',
            'sprout-email/notifications/edit/<emailId:\d+|new>' =>
                'sprout-base-email/notifications/edit-notification-email-template',
            'sprout-email/notifications/settings/edit/<emailId:\d+|new>' =>
                'sprout-base-email/notifications/edit-notification-email-settings-template',
            'sprout-email/notifications/preview/<emailType:campaign|notification|sent>/<emailId:\d+>' => [
                'route' => 'sprout-base-email/notifications/preview'
            ],

            // Campaigns
            'sprout-email/campaigns/<campaignTypeId:\d+>/<emailId:new>' =>
                'sprout-email/campaign-email/edit-campaign-email',

            'sprout-email/campaigns/edit/<emailId:\d+>' =>
                'sprout-email/campaign-email/edit-campaign-email',

            'sprout-email/campaigns' => [
                'template' => 'sprout-base-email/campaigns/index'
            ],

            // Sent Emails
            'sprout-email/sent-email' => [
                'template' => 'sprout-base-sent-email/sent-email/index'
            ],

            // Settings
            'sprout-email/settings/sent-email' => [
                'route' => 'sprout/settings/edit-settings',
                'params' => [
                    'sproutBaseSettingsType' => SentEmailSettingsModel::class
                ]
            ],
            'sprout-email/settings/<settingsSectionHandle:.*>' =>
                'sprout/settings/edit-settings',
            'sprout-email/settings' =>
                'sprout/settings/edit-settings'
        ];
    }
}
