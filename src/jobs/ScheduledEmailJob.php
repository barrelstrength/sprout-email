<?php

namespace barrelstrength\sproutemail\jobs;

use barrelstrength\sproutbasejobs\base\Job;

class ScheduledEmailJob extends Job
{
    public function run()
    {
        parent::run();
        // @todo - Send the emails
    }
}