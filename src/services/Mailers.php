<?php

namespace barrelstrength\sproutemail\services;

use barrelstrength\sproutbase\app\email\base\Mailer;
use barrelstrength\sproutbase\SproutBase;
use barrelstrength\sproutemail\elements\CampaignEmail;
use barrelstrength\sproutemail\models\CampaignType;
use barrelstrength\sproutbase\app\email\models\Response;
use barrelstrength\sproutemail\SproutEmail;
use craft\base\Component;
use Craft;
use yii\base\Exception;

class Mailers extends Component
{
    /**
     * @param CampaignEmail $campaignEmail
     * @param CampaignType  $campaignType
     *
     * @return mixed
     * @throws \Exception
     */
    public function sendCampaignEmail(CampaignEmail $campaignEmail, CampaignType $campaignType)
    {
        $mailer = $campaignType->getMailer();

        if (!$mailer) {
            throw new Exception(Craft::t('sprout-email', 'No mailer with id {id} was found.', ['id' => $campaignType->mailer]));
        }

        /**
         * @var $mailer Mailer
         */
        try {
            return $mailer->sendCampaignEmail($campaignEmail, $campaignType);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $mailerName
     * @param $emailId
     * @param $campaignTypeId
     *
     * @return Response
     * @throws \Exception
     */
    public function getPrepareModal($mailerName, $emailId, $campaignTypeId)
    {
        $mailer = SproutBase::$app->mailers->getMailerByName($mailerName);

        if (!$mailer) {
            throw new Exception(Craft::t('sprout-email', 'No mailer with id {id} was found.', ['id' => $mailerName]));
        }

        /**
         * @var $campaignEmail CampaignEmail
         */
        $campaignEmail = SproutEmail::$app->campaignEmails->getCampaignEmailById($emailId);
        $campaignType = SproutEmail::$app->campaignTypes->getCampaignTypeById($campaignTypeId);
        $response = new Response();

        if ($campaignEmail && $campaignType) {
            try {
                $response->success = true;
                $response->content = $mailer->getPrepareModalHtml($campaignEmail, $campaignType);

                return $response;
            } catch (\Exception $e) {
                $response->success = false;
                $response->message = $e->getMessage();

                return $response;
            }
        } else {
            $name = $mailer->getName();
            $response->success = false;
            $response->message = "<h1>$name</h1><br><p>".Craft::t('sprout-email', 'No actions available for this campaign entry.').'</p>';
        }

        return $response;
    }
}