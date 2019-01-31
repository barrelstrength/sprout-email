<?php

namespace barrelstrength\sproutemail\events\notificationevents;

use barrelstrength\sproutbase\app\email\base\NotificationEvent;

use Craft;
use craft\elements\User;


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
class UsersDelete extends NotificationEvent
{
    public $whenNew = false;

    public $whenUpdated = false;

    public $adminUsers = false;

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
        return User::EVENT_AFTER_DELETE;
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
        return Craft::t('sprout-email', 'When a user is deleted');
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): string
    {
        return Craft::t('sprout-email', 'Triggered when a user is deleted.');
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
        $criteria = User::find();

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

        if (get_class($event->sender) !== User::class) {
            $this->addError('event', Craft::t('sprout-email', 'Event Element does not match craft\elements\Entry class.'));
        }
    }
}
