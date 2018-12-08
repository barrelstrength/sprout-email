<?php

namespace barrelstrength\sproutemail\events\notificationevents;

use barrelstrength\sproutbase\app\email\base\NotificationEvent;
use craft\elements\Entry;
use Craft;
use craft\events\ElementEvent;
use craft\events\ModelEvent;

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
    public function getName()
    {
        return Craft::t('sprout-email', 'When an entry is saved');
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return Craft::t('sprout-email', 'Triggered when an entry is saved.');
    }

    /**
     * @inheritdoc
     *
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function getSettingsHtml($settings = [])
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
     * @return array|\craft\base\ElementInterface|Entry|null
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

    public function rules()
    {
        $rules = parent::rules();

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
        /**
         * @var ElementEvent $event
         */
        $event = $this->event ?? null;

        $isNewEntry = $event->isNew ?? false;

        $matchesWhenNew = $this->whenNew && $isNewEntry ?? false;
        $matchesWhenUpdated = $this->whenUpdated && !$isNewEntry ?? false;

        if (!$matchesWhenNew && !$matchesWhenUpdated) {
            $this->addError('event', Craft::t('sprout-email', 'When an Entry is saved Event does not match any scenarios.'));
        }

        // Make sure new entries are new.
        if (($this->whenNew && !$isNewEntry) && !$this->whenUpdated) {
            $this->addError('event', Craft::t('sprout-email', '"When an entry is created" is selected but the entry is being updated.'));
        }

        // Make sure updated entries are not new
        if (($this->whenUpdated && $isNewEntry) && !$this->whenNew) {
            $this->addError('event', Craft::t('sprout-email', '"When an entry is updated" is selected but the entry is new.'));
        }
    }

    public function validateEvent()
    {
        $event = $this->event ?? null;

        $isAjax    = Craft::$app->request->getIsAjax();
        $isConsole = Craft::$app->request->getIsConsoleRequest();

        // Do not trigger this event on queue job and console request.
        if ($isAjax === true || $isConsole === true) {
            $this->addError('event', Craft::t('sprout-email', 'ElementEvent does not trigger ajax and console request.'));
        }

        if (!$event) {
            $this->addError('event', Craft::t('sprout-email', 'ElementEvent does not exist.'));
        }

        if ($event->sender->getStatus() != Entry::STATUS_LIVE) {
            $this->addError('event', Craft::t('sprout-email', 'ElementEvent only triggers for enabled element.'));
        }

        if (get_class($event->sender) !== Entry::class) {
            $this->addError('event', Craft::t('sprout-email', 'Event Element does not match craft\elements\Entry class.'));
        }
    }

    public function validateSectionIds()
    {
        /**
         * @var ElementEvent $event
         */
        $element = $this->event->sender ?? null;

        $elementId = null;

        if (get_class($element) === Entry::class) {
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

    /**
     * Returns an array of sections suitable for use in checkbox field
     *
     * @return array
     */
    protected function getAllSections()
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
