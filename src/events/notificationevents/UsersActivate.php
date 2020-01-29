<?php
/**
 * @link https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license https://craftcms.github.io/license
 */

namespace barrelstrength\sproutemail\events\notificationevents;

use barrelstrength\sproutbaseemail\base\NotificationEvent;
use Craft;
use craft\elements\User;
use craft\events\UserEvent;
use craft\services\Users;


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
class UsersActivate extends NotificationEvent
{
    /**
     * @inheritdoc
     */
    public function getEventClassName()
    {
        return Users::class;
    }

    /**
     * @inheritdoc
     */
    public function getEventName()
    {
        return Users::EVENT_AFTER_ACTIVATE_USER;
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
        return Craft::t('sprout-email', 'When a user is activated');
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): string
    {
        return Craft::t('sprout-email', 'Triggered when a user is activated.');
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
        $criteria = User::find();

        return $criteria->one();
    }
}
