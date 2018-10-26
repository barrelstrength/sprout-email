<?php

namespace barrelstrength\sproutemail\services;

use barrelstrength\sproutemail\models\Settings;
use craft\mail\Message;
use barrelstrength\sproutemail\elements\SentEmail;
use barrelstrength\sproutemail\models\SentEmailInfoTable;
use craft\base\Component;
use craft\helpers\Json;
use yii\base\Event;
use Craft;
use yii\mail\MailEvent;
use barrelstrength\sproutemail\SproutEmail;

/**
 * Class SentEmails
 *
 * @package barrelstrength\sproutemail\services
 */
class SentEmails extends Component
{
    /**
     * @param MailEvent $event
     *
     * @throws \Throwable
     */
    public function logSentEmail(MailEvent $event)
    {
        // Prepare some variables
        // -----------------------------------------------------------

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

        $variables = $message->variables;

        $infoTable = $variables['info'] ?? null;

        if ($infoTable === null) {
            // If we have info set, grab the custom info that's already prepared
            // If we don't have info, we probably have an email sent by Craft so
            // we can continue with a generic info table model
            $infoTable = new SentEmailInfoTable();

            $infoTable = $this->updateInfoTableWithCraftInfo($infoTable);
        }
        // Sender Info
        $infoTable->senderName = $fromName;
        $infoTable->senderEmail = $fromEmail;

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
     * @param MailEvent $event
     *
     * @throws \Throwable
     */
    public function handleLogSentEmailOnSendEmailError(MailEvent $event)
    {
        $deliveryStatus = $event['deliveryStatus'] ?? null;
        $message = $event['message'] ?? Craft::t('sprout-email', 'Unknown error');

        if (isset($event['variables']['info'])) {
            // Add a few additional variables to our info table
            $event['variables']['info']->deliveryStatus = $deliveryStatus;
            $event['variables']['info']->message = $message;
        } else {
            $infoTable = new SentEmailInfoTable();

            $infoTable->deliveryStatus = $deliveryStatus;
            $infoTable->message = $message;

            $event['variables']['info'] = $infoTable;
        }

        if (isset($event['variables']['info'])) {
            // Add a few additional variables to our info table
            $event['variables']['info']->deliveryStatus = $deliveryStatus;
            $event['variables']['info']->message = $message;
        } else {
            $infoTable = new SentEmailInfoTable();

            $infoTable->deliveryStatus = $deliveryStatus;
            $infoTable->message = $message;

            $event['variables']['info'] = $infoTable;
        }

        SproutEmail::$app->sentEmails->logSentEmail($event);
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

        $plugin = Craft::$app->getPlugins()->getPlugin($pluginHandle);
        $infoTable->source = '';
        $infoTable->sourceVersion = '';

        if ($plugin) {
            $infoTable->source = $plugin->getHandle();
            $infoTable->sourceVersion = $plugin->getHandle().' '.$plugin->getVersion();
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
    public function updateInfoTableWithCraftInfo($infoTable)
    {
        $craftVersion = $this->_getCraftVersion();

        $infoTable->emailType = Craft::t('sprout-email', 'Craft CMS Email');
        $infoTable->source = 'Craft CMS';
        $infoTable->sourceVersion = $craftVersion;
        $infoTable->craftVersion = $craftVersion;

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