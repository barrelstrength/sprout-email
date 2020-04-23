<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   https://craftcms.github.io/license
 */

namespace barrelstrength\sproutemail\events\notificationevents;

use barrelstrength\sproutbaseemail\base\NotificationEvent;
use Craft;
use craft\events\UserEvent;
use craft\records\User as UserRecord;
use yii\web\User;


/**
 * @property string    $eventClassName
 * @property mixed     $description
 * @property mixed     $eventName
 * @property mixed     $name
 * @property mixed     $eventObject
 * @property mixed     $mockEventObject
 * @property string    $eventHandlerClassName
 * @property UserEvent $event
 */
class UsersLogin extends NotificationEvent
{
    /**
     * @inheritdoc
     */
    public function getEventClassName()
    {
        return User::class;
    }

    /**
     * @inheritdoc
     */
    public function getEventName()
    {
        return User::EVENT_AFTER_LOGIN;
    }

    /**
     * @inheritdoc
     */
    public function getEventHandlerClassName()
    {
        return UserEvent::class;
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return Craft::t('sprout-email', 'When a user is logged in.');
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): string
    {
        return Craft::t('sprout-email', 'Triggered when a user is logged in.');
    }

    /**
     * @inheritdoc
     */
    public function getEventObject()
    {
        return $this->event->user;
    }

    /**
     * @inheritdoc
     */
    public function getMockEventObject()
    {
        $criteria = UserRecord::find();

        return $criteria->one();
    }
}
