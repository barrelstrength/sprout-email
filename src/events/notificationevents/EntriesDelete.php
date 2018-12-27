<?php

namespace barrelstrength\sproutemail\events\notificationevents;

use barrelstrength\sproutbase\app\email\base\NotificationEvent;

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
    public function getEventClassName(): string
    {
        return Entry::class;
    }

    /**
     * @inheritdoc
     */
    public function getEventName(): string
    {
        return Entry::EVENT_AFTER_DELETE;
    }

    /**
     * @inheritdoc
     */
    public function getEventHandlerClassName(): string
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

    public function rules(): array
    {
        $rules = parent::rules();

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
