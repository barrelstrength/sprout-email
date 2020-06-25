<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   https://craftcms.github.io/license
 */

namespace barrelstrength\sproutemail;

use barrelstrength\sproutbase\app\email\events\NotificationEmailEvent;
use barrelstrength\sproutbase\app\email\events\notificationevents\EntriesDelete;
use barrelstrength\sproutbase\app\email\events\notificationevents\EntriesSave;
use barrelstrength\sproutbase\app\email\events\notificationevents\Manual;
use barrelstrength\sproutbase\app\email\events\notificationevents\UsersActivate;
use barrelstrength\sproutbase\app\email\events\notificationevents\UsersDelete;
use barrelstrength\sproutbase\app\email\events\notificationevents\UsersSave;
use barrelstrength\sproutbase\app\email\services\NotificationEmailEvents;
use barrelstrength\sproutbase\config\base\SproutBasePlugin;
use barrelstrength\sproutbase\config\configs\CampaignsConfig;
use barrelstrength\sproutbase\config\configs\EmailPreviewConfig;
use barrelstrength\sproutbase\config\configs\NotificationsConfig;
use barrelstrength\sproutbase\config\configs\FieldsConfig;
use barrelstrength\sproutbase\config\configs\ControlPanelConfig;
use barrelstrength\sproutbase\config\configs\ReportsConfig;
use barrelstrength\sproutbase\config\configs\SentEmailConfig;
use barrelstrength\sproutbase\SproutBaseHelper;
use Craft;
use craft\helpers\UrlHelper;
use yii\base\Event;

class SproutEmail extends SproutBasePlugin
{
    const EDITION_LITE = 'lite';
    const EDITION_PRO = 'pro';

    /**
     * @var string
     */
    public $schemaVersion = '4.4.4';

    /**
     * @var string
     */
    public $minVersionRequired = '4.4.7';

    public static function editions(): array
    {
        return [
            self::EDITION_LITE,
            self::EDITION_PRO,
        ];
    }

    public static function getSproutConfigs(): array
    {
        return [
            CampaignsConfig::class,
            NotificationsConfig::class,
            EmailPreviewConfig::class,
            FieldsConfig::class,
            SentEmailConfig::class,
            ReportsConfig::class
        ];
    }

    public function init()
    {
        parent::init();

        SproutBaseHelper::registerModule();

        Event::on(NotificationEmailEvents::class, NotificationEmailEvents::EVENT_REGISTER_EMAIL_EVENT_TYPES, static function(NotificationEmailEvent $event) {
            $event->events[] = EntriesDelete::class;
            $event->events[] = UsersSave::class;
            $event->events[] = UsersDelete::class;
            $event->events[] = UsersActivate::class;
            $event->events[] = Manual::class;
        });
    }

    protected function afterInstall()
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        // Redirect to welcome page
        $url = UrlHelper::cpUrl('sprout/welcome/email');
        Craft::$app->controller->redirect($url)->send();
    }
}
