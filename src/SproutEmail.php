<?php
/**
 * Sprout Email plugin for Craft CMS 3.x
 *
 * Flexible, integrated email marketing and notifications.
 *
 * @link      https://barrelstrengthdesign.com
 * @copyright Copyright (c) 2017 Barrelstrength
 */

namespace barrelstrength\sproutemail;

use barrelstrength\sproutbase\base\BaseSproutTrait;
use barrelstrength\sproutbaseemail\events\NotificationEmailEvent;
use barrelstrength\sproutbaseemail\services\NotificationEmailEvents;
use barrelstrength\sproutbaseemail\SproutBaseEmailHelper;
use barrelstrength\sproutbasefields\SproutBaseFieldsHelper;
use barrelstrength\sproutbaselists\SproutBaseListsHelper;
use barrelstrength\sproutemail\events\notificationevents\EntriesDelete;
use barrelstrength\sproutemail\events\notificationevents\EntriesSave;
use barrelstrength\sproutemail\events\notificationevents\Manual;
use barrelstrength\sproutemail\events\notificationevents\UsersActivate;
use barrelstrength\sproutemail\events\notificationevents\UsersDelete;
use barrelstrength\sproutemail\events\notificationevents\UsersSave;
use barrelstrength\sproutbaseemail\models\Settings;
use barrelstrength\sproutemail\services\App;
use Craft;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use barrelstrength\sproutbase\SproutBaseHelper;
use craft\events\RegisterUserPermissionsEvent;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\mail\BaseMailer;
use yii\mail\MailEvent;

/**
 * Class SproutEmail
 *
 * @author    Barrelstrength
 * @package   SproutEmail
 * @since     3
 *
 *
 * @property array $cpNavItem
 * @property array $userPermissions
 * @property array $cpUrlRules
 */
class SproutEmail extends Plugin
{
    use BaseSproutTrait;

    /**
     * Enable use of SproutEmail::$plugin-> in place of Craft::$app->
     *
     * @var App
     */
    public static $app;

    /**
     * @var string
     */
    public static $pluginHandle = 'sprout-email';

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
    public $schemaVersion = '4.1.0';

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
        SproutBaseListsHelper::registerModule();

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

        Event::on(NotificationEmailEvents::class, NotificationEmailEvents::EVENT_REGISTER_EMAIL_EVENT_TYPES, function(NotificationEmailEvent $event) {
            $event->events[] = EntriesSave::class;
            $event->events[] = EntriesDelete::class;
            $event->events[] = UsersSave::class;
            $event->events[] = UsersDelete::class;
            $event->events[] = UsersActivate::class;
            $event->events[] = Manual::class;
        });

        // Email Tracking
        Event::on(BaseMailer::class, BaseMailer::EVENT_AFTER_SEND, function(MailEvent $event) {
            if ($this->getSettings()->enableSentEmails) {
                SproutEmail::$app->sentEmails->logSentEmail($event);
            }
        });
    }

    /**
     * @return Settings
     */
    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }

    /**
     * @return array
     */
    public function getCpNavItem(): array
    {
        $parent = parent::getCpNavItem();

        // Allow user to override plugin name in sidebar
        if ($this->getSettings()->pluginNameOverride) {
            $parent['label'] = $this->getSettings()->pluginNameOverride;
        }

        $parent['url'] = 'sprout-email';

        $navigation = [];

        $settings = $this->getSettings();

//        if (Craft::$app->getUser()->checkPermission('sproutEmail-editNotifications') && $settings->enableCampaignEmails) {
//            $navigation['subnav']['campaigns'] = [
//                'label' => Craft::t('sprout-email', 'Campaigns'),
//                'url' => 'sprout-email/campaigns'
//            ];
//        }

        if (Craft::$app->getUser()->checkPermission('sproutEmail-editNotifications') && $settings->enableNotificationEmails) {
            $navigation['subnav']['notifications'] = [
                'label' => Craft::t('sprout-email', 'Notifications'),
                'url' => 'sprout-email/notifications'
            ];
        }

        $navigation['subnav']['subscribers'] = [
            'label' => Craft::t('sprout-email', 'Subscribers'),
            'url' => 'sprout-email/subscribers'
        ];

        $navigation['subnav']['lists'] = [
            'label' => Craft::t('sprout-email', 'Lists'),
            'url' => 'sprout-email/lists'
        ];

        $navigation['subnav']['segments'] = [
            'label' => Craft::t('sprout-email', 'Mailing Lists'),
            'url' => 'sprout-email/segments'
        ];

        if (Craft::$app->getUser()->checkPermission('sproutEmail-viewSentEmail') && $settings->enableSentEmails) {
            $navigation['subnav']['sentemails'] = [
                'label' => Craft::t('sprout-email', 'Sent Emails'),
                'url' => 'sprout-email/sentemails'
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

            // Subscribers
            'sprout-email/subscribers/new' =>
                'sprout-base-lists/subscribers/edit-subscriber-template',
            'sprout-email/subscribers/edit/<id:\d+>' =>
                'sprout-base-lists/subscribers/edit-subscriber-template',
            'sprout-email/subscribers/<listHandle:.*>' => [
                'template' => 'sprout-base-lists/subscribers'
            ],
            'sprout-email/subscribers' =>
                'sprout-base-lists/subscribers/subscribers-index-template',

            // Lists
            'sprout-email/lists' =>
                'sprout-base-lists/lists/lists-index-template',
            'sprout-email/lists/new' =>
                'sprout-base-lists/lists/list-edit-template',
            'sprout-email/lists/edit/<listId:\d+>' =>
                'sprout-base-lists/lists/list-edit-template',

            // Segments
            '<pluginHandle:sprout-email>/segments/<dataSourceId:\d+>/new' => [
                'route' => 'sprout-base-reports/reports/edit-report-template',
                'params' => [
                    'viewContext' => 'mailingList',
                ]
            ],
            '<pluginHandle:sprout-email>/segments/<dataSourceId:\d+>/edit/<reportId:\d+>' => [
                'route' => 'sprout-base-reports/reports/edit-report-template',
                'params' => [
                    'viewContext' => 'mailingList',
                ]
            ],
            '<pluginHandle:sprout-email>/segments/view/<reportId:\d+>' => [
                'route' => 'sprout-base-reports/reports/results-index-template',
                'params' => [
                    'viewContext' => 'mailingList',
                ]
            ],
            '<pluginHandle:sprout-email>/segments/<dataSourceId:\d+>' => [
                'route' => 'sprout-base-reports/reports/reports-index-template',
                'params' => [
                    'viewContext' => 'mailingList',
                    'hideSidebar' => true
                ]
            ],
            '<pluginHandle:sprout-email>/segments' => [
                'route' => 'sprout-base-reports/reports/reports-index-template',
                'params' => [
                    'viewContext' => 'mailingList',
                    'hideSidebar' => true
                ]
            ],


            // Sent Emails
            'sprout-email/sentemails' => [
                'template' => 'sprout-base-email/sentemails/index'
            ],

            // Settings
            'sprout-email/settings/<settingsSectionHandle:.*>' =>
                'sprout/settings/edit-settings',
            'sprout-email/settings' =>
                'sprout/settings/edit-settings'
        ];
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
}
