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
    public $emailType;
    public $deliveryType;
    public $deliveryStatus;
    public $message;

    // Sender Info
    public $senderName;
    public $senderEmail;
    public $source;
    public $sourceVersion;
    public $craftVersion;
    public $ipAddress;
    public $userAgent;

    // Email Settings
    public $mailer;
    public $protocol;
    public $hostName;
    public $port;
    public $smtpSecureTransportType;
    public $timeout;
}