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
use barrelstrength\sproutbase\app\email\events\RegisterMailersEvent;
use barrelstrength\sproutbase\app\email\events\NotificationEmailEvent;
use barrelstrength\sproutbase\app\email\events\RegisterSendEmailEvent;

use barrelstrength\sproutbase\app\email\services\NotificationEmailEvents;
use barrelstrength\sproutemail\events\notificationevents\EntriesDelete;
use barrelstrength\sproutemail\events\notificationevents\EntriesSave;
use barrelstrength\sproutemail\events\notificationevents\Manual;
use barrelstrength\sproutemail\events\notificationevents\UsersDelete;
use barrelstrength\sproutemail\events\notificationevents\UsersSave;
use barrelstrength\sproutemail\mailers\CopyPasteMailer;

use barrelstrength\sproutemail\models\Settings;
use barrelstrength\sproutemail\services\App;
use barrelstrength\sproutbase\app\email\services\Mailers;
use Craft;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use barrelstrength\sproutbase\SproutBaseHelper;
use craft\web\UrlManager;
use yii\base\Event;
use yii\mail\BaseMailer;
use yii\mail\MailEvent;

/**
 * Class SproutEmail
 *
 * @author    Barrelstrength
 * @package   SproutEmail
 * @since     3
 *
 */
class SproutEmail extends Plugin
{
    use BaseSproutTrait;

    /**
     * Enable use of SproutEmail::$plugin-> in place of Craft::$app->
     *
     * @var \barrelstrength\sproutemail\services\App
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
    public $schemaVersion = '4.0.0';

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();

        SproutBaseHelper::registerModule();

        $this->setComponents([
            'app' => App::class
        ]);

        self::$app = $this->get('app');

        Craft::setAlias('@sproutemail', $this->getBasePath());

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules['sprout-email'] = ['template' => 'sprout-base-email/index'];
            $event->rules['sprout-email/index'] = ['template' => 'sprout-base-email/index'];

            $event->rules['sprout-email/notifications'] = ['template' => 'sprout-base-email/notifications/index'];
            $event->rules['sprout-email/notifications/edit/<emailId:\d+|new>']
                = 'sprout-base/notifications/edit-notification-email-template';
            $event->rules['sprout-email/settings/notifications/edit/<emailId:\d+|new>']
                = 'sprout-base/notifications/edit-notification-email-settings-template';

            $event->rules['sprout-email/campaigns'] = ['template' => 'sprout-base-email/campaigns/index'];
            $event->rules['sprout-email/campaigns/<campaignTypeId:\d+>/new'] = 'sprout-email/campaign-email/edit-campaign-email';
            $event->rules['sprout-email/campaigns/edit/<emailId:\d+>'] = 'sprout-email/campaign-email/edit-campaign-email';

            $event->rules['sprout-email/sentemails'] = ['template' => 'sprout-base-email/sentemails/index'];

            $event->rules['sprout-email/settings'] = 'sprout-base/settings/edit-settings';
            $event->rules['sprout-email/settings/campaigntypes/edit/<campaignTypeId:\d+|new>'] = 'sprout-email/campaign-type/campaign-settings';
            $event->rules['sprout-email/settings/<settingsSectionHandle:.*>'] = 'sprout-base/settings/edit-settings';

            $event->rules['sprout-email/preview/<emailType:campaign|notification|sent>/<emailId:\d+>'] = ['template' => 'sprout-base-email/_special/preview'];
        });

        Event::on(Mailers::class, Mailers::EVENT_REGISTER_MAILER_TYPES, function(RegisterMailersEvent $event) {
            $event->mailers[] = new CopyPasteMailer();
        });

        Event::on(NotificationEmailEvents::class, NotificationEmailEvents::EVENT_REGISTER_EMAIL_EVENT_TYPES, function(NotificationEmailEvent $event) {
            $event->events[] = EntriesSave::class;
            $event->events[] = EntriesDelete::class;
            $event->events[] = UsersSave::class;
            $event->events[] = UsersDelete::class;
            $event->events[] = Manual::class;
        });

        Event::on(Mailers::class, Mailers::ON_SEND_EMAIL_ERROR, function(Event $event) {
            SproutEmail::$app->sentEmails->handleLogSentEmailOnSendEmailError($event);
        });

        Event::on(Mailers::class, Mailers::ON_SEND_EMAIL, function(RegisterSendEmailEvent $event) {
            SproutEmail::$app->sentEmails->logSentEmail($event);
        });

        Event::on(BaseMailer::class, BaseMailer::EVENT_AFTER_SEND, function(MailEvent $event) {
            // @todo - update logSentEmail to work with Yii's MailEvent
            //SproutEmail::$app->sentEmails->logSentEmail($event);
        });
    }

    /**
     * @return Settings
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @return array
     */
    public function getCpNavItem()
    {
        $parent = parent::getCpNavItem();

        $parent['url'] = 'sprout-email';

        $navigation = [];

        $settings = $this->getSettings();

        if ($settings->enableNotificationEmails) {
            $navigation['subnav']['notifications'] = [
                'label' => Craft::t('sprout-email', 'Notifications'),
                'url' => 'sprout-email/notifications'
            ];
        }

        if ($settings->enableCampaignEmails) {
            $navigation['subnav']['campaigns'] = [
                'label' => Craft::t('sprout-email', 'Campaigns'),
                'url' => 'sprout-email/campaigns'
            ];
        }


        if ($settings->enableSentEmails) {
            $navigation['subnav']['sentemails'] = [
                'label' => Craft::t('sprout-email', 'Sent Emails'),
                'url' => 'sprout-email/sentemails'
            ];
        }

        $navigation['subnav']['settings'] = [
            'label' => Craft::t('sprout-email', 'Settings'),
            'url' => 'sprout-email/settings/general'
        ];

        return array_merge($parent, $navigation);
    }
}
