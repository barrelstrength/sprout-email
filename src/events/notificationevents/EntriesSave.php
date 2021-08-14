<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   https://craftcms.github.io/license
 */

namespace barrelstrength\sproutemail\events\notificationevents;

use barrelstrength\sproutbaseemail\base\NotificationEvent;
use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\elements\Entry;
use craft\events\ElementEvent;
use craft\events\ModelEvent;
use craft\helpers\ElementHelper;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Exception;

/**
 *
 * @property string                            $eventHandlerClassName
 * @property array                             $allSections
 * @property Entry|null|array|ElementInterface $mockEventObject
 * @property null                              $eventObject
 * @property string                            $name
 * @property mixed                             $eventName
 * @property string                            $description
 * @property string                            $eventClassName
 */
class EntriesSave extends NotificationEvent
{
    public $whenNew = false;

    public $whenUpdated = false;

    public $sectionIds;

    public $availableSections;

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
        return Entry::EVENT_AFTER_SAVE;
    }

    /**
     * @inheritdoc
     */
    public function getEventHandlerClassName()
    {
        return ModelEvent::class;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return Craft::t('sprout-email', 'When an entry is saved');
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return Craft::t('sprout-email', 'Triggered when an entry is saved.');
    }

    /**
     * @inheritdoc
     *
     * @param array $settings
     *
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     */
    public function getSettingsHtml($settings = []): string
    {
        if (!$this->availableSections) {
            $this->availableSections = $this->getAllSections();
        }

        return Craft::$app->getView()->renderTemplate('sprout-base-email/_components/events/save-entry', [
            'event' => $this
        ]);
    }

    public function getEventObject()
    {
        $event = $this->event ?? null;

        return $event->sender ?? null;
    }

    /**
     * @return array|ElementInterface|Entry|null
     */
    public function getMockEventObject()
    {
        $criteria = Entry::find();

        $ids = $this->sectionIds;

        if (is_array($ids) && count($ids)) {

            $id = array_shift($ids);

            $criteria->where([
                'sectionId' => $id
            ]);
        }

        return $criteria->one();
    }

    /**
     * @return array
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [
            'whenNew', 'required', 'when' => function() {
                return $this->whenUpdated == false;
            }
        ];
        $rules[] = [
            'whenUpdated', 'required', 'when' => function() {
                return $this->whenNew == false;
            }
        ];
        $rules[] = [['whenNew', 'whenUpdated'], 'validateWhenTriggers'];
        $rules[] = [['event'], 'validateEvent'];
        $rules[] = ['sectionIds', 'default', 'value' => false];
        $rules[] = [['sectionIds'], 'validateSectionIds'];

        return $rules;
    }

    public function validateWhenTriggers()
    {
        /** @var ElementEvent $event */
        $event = $this->event ?? null;

        /** @var ElementInterface $entry */
        $entry = $event->sender;

        if (!$this->isNewLiveEntry($entry) && !$this->isUpdatedLiveEntry($entry)) {
            $this->addError('event', Craft::t('sprout-email', 'The `When an Entry is saved Event` Notification Event does not match a new or updated live entry.'));
        }

        $matchesWhenNew = $this->whenNew && $this->isNewLiveEntry($entry) ?? false;
        $matchesWhenUpdated = $this->whenUpdated && $this->isUpdatedLiveEntry($entry) ?? false;

        if (!$matchesWhenNew && !$matchesWhenUpdated) {
            $this->addError('event', Craft::t('sprout-email', 'When an Entry is saved Event does not match any scenarios.'));
        }

        // Make sure new entries are new.
        if (($this->whenNew && !$this->isNewLiveEntry($entry)) && !$this->whenUpdated) {
            $this->addError('event', Craft::t('sprout-email', '"When an entry is created" is selected but the entry is being updated.'));
        }

        // Make sure updated entries are not new
        if (($this->whenUpdated && !$this->isUpdatedLiveEntry($entry)) && !$this->whenNew) {
            $this->addError('event', Craft::t('sprout-email', '"When an entry is updated" is selected but the entry is new.'));
        }
    }

    public function validateEvent()
    {
        /** @var ElementEvent $event */
        $event = $this->event ?? null;

        /** @var ElementInterface $entry */
        $entry = $event->sender;

        $matchesWhenNew = $this->whenNew && $this->isNewLiveEntry($entry) ?? false;
        $matchesWhenUpdated = $this->whenUpdated && $this->isUpdatedLiveEntry($entry) ?? false;

        if (!$event) {
            $this->addError('event', Craft::t('sprout-email', 'ElementEvent does not exist.'));
        }

        if (get_class($event->sender) !== Entry::class) {
            $this->addError('event', Craft::t('sprout-email', 'The `EntriesSave` Notification Event does not match the craft\elements\Entry class.'));
        }
    }

    public function validateSectionIds()
    {
        /**
         * @var ElementEvent $event
         */
        $element = $this->event->sender ?? null;
        $elementId = null;

        if (get_class($element) === Entry::class && $element !== null) {
            $elementId = $element->getSection()->id;
        }

        // If entry sections settings are unchecked
        if ($this->sectionIds == false) {
            $this->addError('event', Craft::t('sprout-email', 'No Section has been selected.'));
        }

        // If any section ids were checked, make sure the entry belongs in one of them
        if (is_array($this->sectionIds) AND !in_array($elementId, $this->sectionIds, false)) {
            $this->addError('event', Craft::t('sprout-email', 'Saved Entry Element does not match any selected Sections.'));
        }
    }

    // Does not match the scenario where a new entry is disabled
    // or where a new entry has a future publish date.
    protected function isNewLiveEntry($element): bool
    {
        return
            $element->firstSave &&
            $element->getIsCanonical() &&
            $element->getStatus() === Entry::STATUS_LIVE &&
            !ElementHelper::isDraftOrRevision($element) &&
            !$element->resaving &&
            !$element->propagating;
    }

    // Matches the scenario where a new, disabled entry gets updated to enabled
    protected function isUpdatedLiveEntry($element): bool
    {
        return
            !$element->firstSave &&
            $element->getIsCanonical() &&
            $element->getStatus() === Entry::STATUS_LIVE &&
            !ElementHelper::isDraftOrRevision($element) &&
            !$element->resaving &&
            !$element->propagating;
    }

    /**
     * Returns an array of sections suitable for use in checkbox field
     *
     * @return array
     */
    protected function getAllSections(): array
    {
        $result = Craft::$app->sections->getAllSections();
        $options = [];

        foreach ($result as $key => $section) {
            $options[] = [
                'label' => $section->name,
                'value' => $section->id
            ];
        }

        return $options;
    }
}
