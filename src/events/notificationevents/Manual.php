<?php

namespace barrelstrength\sproutemail\events\notificationevents;

use barrelstrength\sproutbase\app\email\base\NotificationEvent;

use Craft;


class Manual extends NotificationEvent
{
    /**
     * @inheritdoc
     */
    public function getEventClassName()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getEventName()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getEventHandlerClassName()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return Craft::t('sprout-email', 'None');
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return Craft::t('sprout-email', 'The manual event is never triggered.');
    }
}
