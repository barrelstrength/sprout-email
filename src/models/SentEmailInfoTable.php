<?php

namespace barrelstrength\sproutemail\models;

use craft\base\Model;

/**
 * Class SentEmailInfoTable
 *
 * @package barrelstrength\sproutemail\models
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
     * @var Sent, Error
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
    public $smtpSecureTransportType;
    /**
     * @var
     */
    public $timeout;
}