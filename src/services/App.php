<?php

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
     * @var CampaignTypes
     */
    public $campaignTypes;

    /**
     * @var CampaignEmails
     */
    public $campaignEmails;

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
        $this->campaignTypes = new CampaignTypes();
        $this->campaignEmails = new CampaignEmails();
        $this->sentEmails = new SentEmails();
        $this->utilities = Utilities::Instance();
    }
}
