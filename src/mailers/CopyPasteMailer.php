<?php

namespace barrelstrength\sproutemail\mailers;

use barrelstrength\sproutbase\app\email\base\Mailer;
use barrelstrength\sproutbase\app\email\base\CampaignEmailSenderInterface;
use barrelstrength\sproutbase\app\email\web\assets\email\CopyPasteAsset;
use barrelstrength\sproutemail\elements\CampaignEmail;
use barrelstrength\sproutemail\models\CampaignType;
use barrelstrength\sproutbase\app\email\models\Response;
use Craft;
use craft\base\Element;

class CopyPasteMailer extends Mailer implements CampaignEmailSenderInterface
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'Copy/Paste';
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return Craft::t('sprout-email', 'Copy and paste your email campaigns to better (or worse) places.');
    }

    /**
     * @return bool
     */
    public function hasLists()
    {
        return false;
    }

    /**
     * @return string
     */
    public function getActionForPrepareModal()
    {
        return 'sprout-email/campaign-email/send-campaign-email';
    }

    /**
     * @param CampaignEmail $campaignEmail
     * @param CampaignType  $campaignType
     *
     * @return mixed
     */
    public function getPrepareModalHtml(CampaignEmail $campaignEmail, CampaignType $campaignType)
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
     * @return mixed
     * @throws \Exception
     */
    public function sendCampaignEmail(CampaignEmail $campaignEmail, CampaignType $campaignType)
    {
        try {
            $variables = [
                'email' => $campaignEmail,
                'campaignType' => $campaignType
            ];

            $mailer = $campaignType->getMailer();
            $message = $mailer->getMessage($campaignEmail);

            $response = new Response();
            $response->success = true;
            $response->content = Craft::$app->getView()->renderPageTemplate('sprout-base-email/_components/mailers/copypaste/schedulecampaignemail',
                [
                    'email' => $campaignEmail,
                    'html' => $campaignEmail->getEmailTemplates()->getHtmlBody(),
                    'text' => $campaignEmail->getEmailTemplates()->getTextBody()
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
     * @throws \Exception
     */
    public function sendTestCampaignEmail(CampaignEmail $campaignEmail, CampaignType $campaignType, array $emails = [])
    {
        return $this->sendCampaignEmail($campaignEmail, $campaignType);
    }

    public function getRecipientsHtml($campaignEmail)
    {
        return "";
    }

    /**
     * Override campaign email validation when saving a new campaign email
     *
     * @param Element|CampaignEmail $email
     *
     * @return Element
     */
    public function beforeValidate(Element $email)
    {
        $user = Craft::$app->user->getIdentity();

        $email->fromName = $user->username;
        $email->fromEmail = $user->email;
        $email->replyToEmail = $user->email;

        return $email;
    }

}
