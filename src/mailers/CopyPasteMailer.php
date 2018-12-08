<?php

namespace barrelstrength\sproutemail\mailers;

use barrelstrength\sproutbase\app\email\base\Mailer;
use barrelstrength\sproutbase\app\email\base\CampaignEmailSenderInterface;
use barrelstrength\sproutbase\app\email\web\assets\email\CopyPasteAsset;
use barrelstrength\sproutemail\elements\CampaignEmail;
use barrelstrength\sproutemail\models\CampaignType;
use barrelstrength\sproutbase\app\email\models\ModalResponse;
use Craft;

class CopyPasteMailer extends Mailer implements CampaignEmailSenderInterface
{
    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Copy/Paste';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return Craft::t('sprout-email', 'Copy and paste your email campaigns to better (or worse) places.');
    }

    /**
     * @inheritdoc
     */
    public function hasSender(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function hasRecipients(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function hasLists(): bool
    {
        return false;
    }

    /**
     * @return string
     */
    public function getActionForPrepareModal(): string
    {
        return 'sprout-email/campaign-email/send-campaign-email';
    }

    /**
     * @param CampaignEmail $campaignEmail
     * @param CampaignType  $campaignType
     *
     * @return mixed
     */
    public function getPrepareModalHtml(CampaignEmail $campaignEmail, CampaignType $campaignType): string
    {
        return '';
    }

    /**
     * Gives mailers the ability to include their own modal resources and register their dynamic action handlers
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function includeModalResources()
    {
        Craft::$app->getView()->registerAssetBundle(CopyPasteAsset::class);
    }

    /**
     * @param CampaignEmail $campaignEmail
     * @param CampaignType  $campaignType
     *
     * @return ModalResponse|mixed|null
     * @throws \Throwable
     */
    public function sendCampaignEmail(CampaignEmail $campaignEmail, CampaignType $campaignType)
    {
        try {
            $response = new ModalResponse();
            $response->success = true;

            $emailTemplates = $campaignEmail->getEmailTemplates();

            $response->content = Craft::$app->getView()->renderPageTemplate('sprout-base-email/_components/mailers/copypaste/schedulecampaignemail',
                [
                    'email' => $campaignEmail,
                    'html' => $emailTemplates->getHtmlBody(),
                    'text' => $emailTemplates->getTextBody()
                ]);

            return $response;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @todo - change method signature and remove $emails in favor of $campaignEmail->getRecipients()
     *
     * @inheritdoc
     *
     * @param CampaignEmail $campaignEmail
     * @param CampaignType  $campaignType
     * @param array         $emails
     *
     * @return ModalResponse|mixed|null
     * @throws \Throwable
     */
    public function sendTestCampaignEmail(CampaignEmail $campaignEmail, CampaignType $campaignType, array $emails = [])
    {
        return $this->sendCampaignEmail($campaignEmail, $campaignType);
    }

    public function getRecipientsHtml($campaignEmail): string
    {
        return '';
    }

    /**
     * Override campaign email validation when saving a new campaign email
     */
    public function beforeValidate()
    {
        $user = Craft::$app->user->getIdentity();

        $this->emailElement->fromName = $user->username;
        $this->emailElement->fromEmail = $user->email;
        $this->emailElement->replyToEmail = $user->email;
    }

}
