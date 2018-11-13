<?php

namespace barrelstrength\sproutemail\services;

use barrelstrength\sproutbase\app\email\enums\DeliveryStatus;
use barrelstrength\sproutbase\app\email\enums\DeliveryType;
use barrelstrength\sproutbase\app\email\jobs\DeleteSentEmails;
use barrelstrength\sproutemail\models\Settings;
use craft\base\Plugin;
use craft\mail\Mailer as CraftMailer;
use craft\mail\Message;
use barrelstrength\sproutemail\elements\SentEmail;
use barrelstrength\sproutemail\models\SentEmailInfoTable;
use craft\base\Component;
use craft\helpers\Json;
use craft\mail\transportadapters\Smtp;
use yii\base\Event;
use Craft;
use yii\mail\MailEvent;

/**
 * Class SentEmails
 *
 * @package barrelstrength\sproutemail\services
 */
class SentEmails extends Component
{
    /**
     * Default limit for the Sent Email trimming
     */
    const SENT_EMAIL_DEFAULT_LIMIT = 5000;

    /**
     * The name of the variable used for the Email Message variables where we pass sent email info.
     */
    const SENT_EMAIL_MESSAGE_VARIABLE = 'sprout-sent-email-info';

    /**
     * @param MailEvent $event
     *
     * @throws \Throwable
     */
    public function logSentEmail(MailEvent $event)
    {
        /**
         * @var $message Message
         */
        $message = $event->message;

        $from = $message->getFrom();
        $fromEmail = '';
        $fromName = '';
        if ($from) {
            $fromEmail = ($res = array_keys($from)) ? $res[0] : '';
            $fromName = ($res = array_values($from)) ? $res[0] : '';
        }

        // We manage the info we track using the Message variables
        $variables = $message->variables;

        /**
         * @var $infoTable SentEmailInfoTable
         */
        $infoTable = $variables[SentEmails::SENT_EMAIL_MESSAGE_VARIABLE] ?? null;

        // Populate what we can for the Info Table for System Messages
        if (get_class($message->mailer) === CraftMailer::class) {
            $infoTable = new SentEmailInfoTable();
            $infoTable = $this->updateInfoTableWithCraftInfo($message, $infoTable);
        }

        // Sender Info
        $infoTable->senderName = $fromName;
        $infoTable->senderEmail = $fromEmail;

        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $infoTable->ipAddress = 'Console Request';
            $infoTable->userAgent = 'Console Request';
        } else {
            $infoTable->ipAddress = Craft::$app->getRequest()->getUserIP();
            $infoTable->userAgent = Craft::$app->getRequest()->getUserAgent();
        }

        $emailSettings = Craft::$app->getSystemSettings()->getEmailSettings();

        $transportType = new $emailSettings->transportType();
        $transportType->setAttributes($emailSettings->transportSettings);
        $infoTable->transportType = $transportType::displayName();

        // If SMTP
        if (get_class($transportType) === Smtp::class) {
            $infoTable->host = $transportType->host;
            $infoTable->port = $transportType->port;
            $infoTable->username = $transportType->username;
            $infoTable->encryptionMethod = $transportType->encryptionMethod;
            $infoTable->timeout = $transportType->timeout;
        }

        if ($event->isSuccessful) {
            $infoTable->deliveryStatus = DeliveryStatus::Sent;
        } else {
            $infoTable->deliveryStatus = DeliveryStatus::Error;
            $infoTable->message = $message;
        }

        $this->saveSentEmail($message, $infoTable);
    }

    /**
     * @param Event $event
     *
     * @throws \Throwable
     */
    public function logSentEmailCampaign(Event $event)
    {
        // Prepare some variables
        // -----------------------------------------------------------

        $emailModel = $event['emailModel'];
        $campaign = $event['campaign'];

        // If we have info set, grab the custom info that's already prepared
        // If we don't have info, we probably have an email sent by Craft so
        // we can continue with our generic info table model
        $infoTable = new SentEmailInfoTable();

        // Prepare our info table settings for Campaigns
        // -----------------------------------------------------------

        // General Info
        $infoTable->emailType = Craft::t('sprout-email', 'Campaign');

        // Sender Info
        $infoTable->senderName = $emailModel->fromName;
        $infoTable->senderEmail = $emailModel->fromEmail;

        // Email Settings
        $infoTable->mailer = ucwords($campaign->mailer);

        $this->saveSentEmail($emailModel, $infoTable);
    }

    /**
     * Save email snapshot using the Sent Email Element Type
     *
     * @param Message            $message
     * @param SentEmailInfoTable $infoTable
     *
     * @return SentEmail|bool
     * @throws \Throwable
     */
    public function saveSentEmail(Message $message, SentEmailInfoTable $infoTable)
    {
        $from = $message->getFrom();
        $fromEmail = '';
        $fromName = '';
        if ($from) {
            $fromEmail = ($res = array_keys($from)) ? $res[0] : '';
            $fromName = ($res = array_values($from)) ? $res[0] : '';
        }

        $to = $message->getTo();
        $toEmail = '';
        if ($to) {
            $toEmail = ($res = array_keys($to)) ? $res[0] : '';
        }

        // Make sure we should be saving Sent Emails
        // -----------------------------------------------------------
        $plugin = Craft::$app->getPlugins()->getPlugin('sprout-email');

        if ($plugin) {
            /**
             * @var $settings Settings
             */
            $settings = $plugin->getSettings();

            if ($settings != null AND !$settings->enableSentEmails) {
                return false;
            }

            $this->cleanUpSentEmails();
        }

        // decode subject if it is encoded
        $isEncoded = preg_match("/=\?UTF-8\?B\?(.*)\?=/", $message->getSubject(), $matches);

        if ($isEncoded) {
            $encodedString = $matches[1];
            $subject = base64_decode($encodedString);
        } else {
            $subject = $message->getSubject();
        }

        $sentEmail = new SentEmail();

        $sentEmail->title = $subject;
        $sentEmail->emailSubject = $subject;
        $sentEmail->fromEmail = $fromEmail;
        $sentEmail->fromName = $fromName;
        $sentEmail->toEmail = $toEmail;

        $children = $message->getSwiftMessage()->getChildren();

        if ($children) {
            foreach ($children as $child) {
                if ($child->getContentType() == 'text/html') {
                    $sentEmail->htmlBody = $child->getBody();
                }

                if ($child->getContentType() == 'text/plain') {
                    $sentEmail->body = $child->getBody();
                }
            }
        }

        if ($infoTable->deliveryStatus == 'failed') {
            $sentEmail->status = 'failed';
        }

        // Remove deliveryStatus as we can determine it from the status in the future
        $infoTable = $infoTable->getAttributes();
        unset($infoTable['deliveryStatus']);
        $sentEmail->info = Json::encode($infoTable);

        try {
            if (Craft::$app->getElements()->saveElement($sentEmail)) {
                return $sentEmail;
            }
        } catch (\Exception $e) {
            Craft::error($e->getMessage(), 'sprout-email');
        }

        return false;
    }

    public function cleanUpSentEmails() {
        /**
         * @var $settings Settings
         */
        $plugin = Craft::$app->getPlugins()->getPlugin('sprout-email');

        if (!$plugin) {
            return false;
        }

        $settings = $plugin->getSettings();

        // Default to 5000 if no integer is found in settings
        $sentEmailsLimit = is_int((int)$settings->sentEmailsLimit)
            ? (int)$settings->sentEmailsLimit
            : static::SENT_EMAIL_DEFAULT_LIMIT;

        if ($sentEmailsLimit > 0) {
            $sentEmailJob = new DeleteSentEmails();
            $sentEmailJob->limit = $sentEmailsLimit;
            $sentEmailJob->siteId = Craft::$app->getSites()->getCurrentSite()->id;

            // Call the Delete Sent Emails job
            Craft::$app->queue->push($sentEmailJob);
        }
    }

    /**
     * Create a SproutEmail_SentEmailInfoTableModel with the Craft and plugin info values
     *
     * @param       $pluginHandle
     * @param array $values
     *
     * @return SentEmailInfoTable
     */
    public function createInfoTableModel($pluginHandle, array $values = [])
    {
        $infoTable = new SentEmailInfoTable();
        $infoTable->setAttributes($values, false);

        /**
         * @var $plugin Plugin
         */
        $plugin = Craft::$app->getPlugins()->getPlugin($pluginHandle);
        $infoTable->source = '';
        $infoTable->sourceVersion = '';

        if ($plugin) {
            $infoTable->source = $plugin::getInstance()->name;
            $infoTable->sourceVersion = $plugin::getInstance()->name.' '.$plugin->getVersion();
        }

        $craftVersion = $this->_getCraftVersion();

        $infoTable->craftVersion = $craftVersion;

        return $infoTable;
    }

    /**
     * Update the SproutEmail_SentEmailInfoTableModel based on the emailKey
     *
     * @param $infoTable
     *
     * @return mixed
     */
    public function updateInfoTableWithCraftInfo(Message $message, SentEmailInfoTable $infoTable)
    {
        $craftVersion = $this->_getCraftVersion();

        $infoTable->emailType = Craft::t('sprout-email', 'System Message');
        $infoTable->source = 'Craft CMS';
        $infoTable->sourceVersion = $craftVersion;
        $infoTable->craftVersion = $craftVersion;

        if ($message->key === 'test_email') {
            $infoTable->deliveryType = DeliveryType::Test;
        }

        $infoTable->mailer = Craft::t('sprout-email', 'Craft Mailer');

        return $infoTable;
    }

    /**
     * Render our HTML and Text email templates like Craft does
     *
     * Craft provides us the original email model, so we need to process the
     * HTML and Text body fields again in order to store the email content as
     * it was sent.
     *
     * @param $emailModel
     * @param $variables
     *
     * @return mixed
     */
    protected function renderEmailContentLikeCraft($emailModel, $variables)
    {
        if ($emailModel->htmlBody) {
            $renderedHtmlBody = Craft::$app->getView()->renderString($emailModel->htmlBody, $variables);
            $renderedTextBody = Craft::$app->getView()->renderString($emailModel->body, $variables);
        } else {
            $renderedHtmlBody = Craft::$app->getView()->renderString($emailModel->body, $variables);
            $renderedTextBody = Craft::$app->getView()->renderString($emailModel->body, $variables);
        }

        $emailModel->htmlBody = $renderedHtmlBody;
        $emailModel->body = $renderedTextBody;

        return $emailModel;
    }

    private function _getCraftVersion()
    {
        $version = Craft::$app->getVersion();
        $craftVersion = '';

        if (version_compare($version, '2.6.2951', '>=')) {
            $craftVersion = 'Craft '.Craft::$app->getEditionName().' '.$version;
        }

        return $craftVersion;
    }
}