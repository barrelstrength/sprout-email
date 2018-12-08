<?php

namespace barrelstrength\sproutemail\models;

use craft\base\Model;
use Craft;

/**
 * Class SentEmailInfoTable
 *
 * @package barrelstrength\sproutemail\models
 *
 * @property array $deliveryStatuses
 * @property array $emailTypes
 * @property array $deliveryTypes
 */
class SentEmailInfoTable extends Model
{
    // General Info
    /**
     * The type of email being sent.
     *
     * @var string Notification Email, Campaign Email, System Message
     */
    public $emailType;
    /**
     * The type of delivery
     *
     * @var string Live, Test
     */
    public $deliveryType;
    /**
     * The status of the email that was sent
     *
     * @var string Sent, Error
     */
    public $deliveryStatus;
    /**
     * Any response or error message generated while sending or attempting to send the email
     *
     * @var string
     */
    public $message;

    // Sender Info
    /**
     * The From Name
     *
     * @var string
     */
    public $senderName;
    /**
     * The From Email
     *
     * @var string
     */
    public $senderEmail;
    /**
     * The plugin or module that initiated sending the email
     *
     * @var string
     */
    public $source;
    /**
     * The version number of the plugin or module that initiated sending the email
     *
     * @var string
     */
    public $sourceVersion;
    /**
     * The version of Craft being used while sending the email
     *
     * @var string
     */
    public $craftVersion;
    /**
     * The IP Address of the request when sending the email
     *
     * @var string
     */
    public $ipAddress;
    /**
     * The User Agent of the request when sending the email
     *
     * @var string
     */
    public $userAgent;

    // Email Settings
    /**
     * @var
     */
    public $mailer;
    /**
     * @var
     */
    public $transportType;
    /**
     * @var
     */
    public $protocol;
    /**
     * @var
     */
    public $host;
    /**
     * @var
     */
    public $port;
    /**
     * @var
     */
    public $username;
    /**
     * @var
     */
    public $encryptionMethod;
    /**
     * @var
     */
    public $timeout;

    public function getEmailTypes()
    {
        return [
            'Campaign' => Craft::t('sprout-email', 'Campaign'),
            'Notification' => Craft::t('sprout-email', 'Notification'),
            'Resent' => Craft::t('sprout-email', 'Resent'),
            'Sent' => Craft::t('sprout-email', 'Sent'),
            'System' => Craft::t('sprout-email', 'System Message')
        ];
    }

    public function getDeliveryStatuses()
    {
        return [
            'Sent' => Craft::t('sprout-email', 'Sent'),
            'Error' => Craft::t('sprout-email', 'Error')
        ];
    }

    public function getDeliveryTypes()
    {
        return [
            'Live' => Craft::t('sprout-email', 'Live'),
            'Test' => Craft::t('sprout-email', 'Test')
        ];
    }
}