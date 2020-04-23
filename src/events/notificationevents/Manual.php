<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   https://craftcms.github.io/license
 */

namespace barrelstrength\sproutemail\events\notificationevents;

use barrelstrength\sproutbaseemail\base\NotificationEvent;
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
