<?php

namespace barrelstrength\sproutemail\events\notificationevents;

use barrelstrength\sproutbase\app\email\base\NotificationEvent;

use Craft;


/**
 *
 * @property null  $eventHandlerClassName
 * @property mixed $name
 * @property null  $eventName
 * @property mixed $description
 * @property null  $eventClassName
 */
class Manual extends NotificationEvent
{
    /**
     * @inheritdoc
     */
    public function getEventClassName(): string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getEventName(): string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getEventHandlerClassName(): string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return Craft::t('sprout-email', 'None');
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): string
    {
        return Craft::t('sprout-email', 'The manual event is never triggered.');
    }
}
