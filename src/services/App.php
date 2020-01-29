<?php
/**
 * @link https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license https://craftcms.github.io/license
 */

namespace barrelstrength\sproutemail\services;


use craft\base\Component;


/**
 * App Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Barrelstrength
 * @package   SproutEmail
 * @since     3
 */
class App extends Component
{

    /**
     * @var SentEmails
     */
    public $sentEmails;

    /**
     * @var Utilities
     */
    public $utilities;

    public function init()
    {
        $this->sentEmails = new SentEmails();
        $this->utilities = Utilities::Instance();
    }
}
