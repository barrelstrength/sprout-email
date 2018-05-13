<?php

namespace barrelstrength\sproutemail\services;

use barrelstrength\sproutemail\models\SentEmailInfoTable;
use barrelstrength\sproutemail\SproutEmail;
use craft\base\Component;
use Craft;
use yii\base\Event;

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
     * @var Mailers
     */
    public $mailers;

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
        $this->mailers = new Mailers();
        $this->campaignTypes = new CampaignTypes();
        $this->campaignEmails = new CampaignEmails();
        $this->sentEmails = new SentEmails();
        $this->utilities = Utilities::Instance();
    }

    /**
     * @param      $name
     * @param null $default
     *
     * @return mixed|null
     * @throws \yii\base\InvalidConfigException
     */
    public function getConfig($name, $default = null)
    {
        $configs = Craft::$app->getConfig()->getConfigSettings('general');

        return is_array($configs) && isset($configs->$name) ? $configs->$name : $default;
    }

    /**
     * @param Event $event
     *
     * @throws \Throwable
     */
    public function handleLogSentEmailOnSendEmailError(Event $event)
    {
        $deliveryStatus = $event->params['deliveryStatus'] ?? null;
        $message = $event->params['message'] ?? Craft::t('sprout-email', 'Unknown error');

        if (isset($event->params['variables']['info'])) {
            // Add a few additional variables to our info table
            $event->params['variables']['info']->deliveryStatus = $deliveryStatus;
            $event->params['variables']['info']->message = $message;
        } else {
            // This is for logging errors before sproutEmail()->sendEmail is called.
            $infoTable = new SentEmailInfoTable();

            $infoTable->deliveryStatus = $deliveryStatus;
            $infoTable->message = $message;

            $event->params['variables']['info'] = $infoTable;
        }

        if (isset($event->params['variables']['info'])) {
            // Add a few additional variables to our info table
            $event->params['variables']['info']->deliveryStatus = $deliveryStatus;
            $event->params['variables']['info']->message = $message;
        } else {
            // This is for logging errors before sproutEmail()->sendEmail is called.
            $infoTable = new SentEmailInfoTable();

            $infoTable->deliveryStatus = $deliveryStatus;
            $infoTable->message = $message;

            $event->params['variables']['info'] = $infoTable;
        }

        SproutEmail::$app->sentEmails->logSentEmail($event);
    }
}
