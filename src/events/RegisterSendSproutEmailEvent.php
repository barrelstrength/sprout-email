<?php
/**
 * @link https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license https://craftcms.github.io/license
 */

namespace barrelstrength\sproutemail\events;

use yii\base\Event;

class RegisterSendSproutEmailEvent extends Event
{
    public $campaignEmail;

    public $emailModel;

    public $campaign;
}