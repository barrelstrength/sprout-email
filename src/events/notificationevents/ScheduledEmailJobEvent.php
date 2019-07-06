<?php

namespace barrelstrength\sproutemail\events\notificationevents;

use barrelstrength\sproutemail\jobs\ScheduledEmailJob;
use barrelstrength\sproutbaseemail\base\ScheduledJobEvent;

/**
 * @property string $jobType
 */
class ScheduledEmailJobEvent extends ScheduledJobEvent
{
    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'Scheduled Email Job';
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): string
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function getJobType(): string
    {
        return ScheduledEmailJob::class;
    }
}