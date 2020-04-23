<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   https://craftcms.github.io/license
 */

namespace barrelstrength\sproutemail\events\notificationevents;

use barrelstrength\sproutbaseemail\base\NotificationEvent;
use Craft;
use craft\elements\Entry;
use yii\base\Event;


/**
 *
 * @property null   $eventHandlerClassName
 * @property mixed  $mockEventObject
 * @property null   $eventObject
 * @property mixed  $name
 * @property mixed  $eventName
 * @property mixed  $description
 * @property string $eventClassName
 */
class EntriesDelete extends NotificationEvent
{
    /**
     * @inheritdoc
     */
    public function getEventClassName()
    {
        return Entry::class;
    }

    /**
     * @inheritdoc
     */
    public function getEventName()
    {
        return Entry::EVENT_AFTER_DELETE;
    }

    /**
     * @inheritdoc
     */
    public function getEventHandlerClassName()
    {
        return Event::class;
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return Craft::t('sprout-email', 'When an entry is deleted');
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): string
    {
        return Craft::t('sprout-email', 'Triggered when an entry is deleted.');
    }

    /**
     * @inheritdoc
     */
    public function getEventObject()
    {
        $event = $this->event ?? null;

        return $event->sender ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getMockEventObject()
    {
        $criteria = Entry::find();

        return $criteria->one();
    }

    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['event'], 'validateEvent'];

        return $rules;
    }

    public function validateEvent()
    {
        $event = $this->event ?? null;

        if (!$event) {
            $this->addError('event', Craft::t('sprout-email', 'ElementEvent does not exist.'));
        }

        if (get_class($event->sender) !== Entry::class) {
            $this->addError('event', Craft::t('sprout-email', 'Event Element does not match craft\elements\Entry class.'));
        }
    }
}
