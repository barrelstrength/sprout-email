<?php

namespace barrelstrength\sproutemail\events;

use yii\base\Event;

class RegisterSendSproutEmailEvent extends Event
{
    public $campaignEmail;

    public $emailModel;

    public $campaign;
}