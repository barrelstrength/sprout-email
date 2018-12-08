<?php

namespace barrelstrength\sproutemail\services;

use barrelstrength\sproutbase\app\email\base\Mailer;
use barrelstrength\sproutemail\records\CampaignEmail as CampaignEmailRecord;
use barrelstrength\sproutemail\elements\CampaignEmail;
use barrelstrength\sproutemail\models\CampaignType;
use barrelstrength\sproutbase\app\email\models\ModalResponse;
use barrelstrength\sproutemail\SproutEmail;
use craft\base\Component;
use Craft;
use craft\helpers\DateTimeHelper;
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
        /**
         * @var $mailer Mailer
         */
        $mailer = $campaignType->getMailer();

        if (!$mailer) {
            throw new Exception(Craft::t('sprout-email', 'No mailer with id {id} was found.', ['id' => $campaignType->mailer]));
        }

        /**
         * @var $mailer Mailer
         */
        try {
            $response = $mailer->sendCampaignEmail($campaignEmail, $campaignType);

            if ($response) {
                // Update dateSent to change mark status
                $record = CampaignEmailRecord::findOne($campaignEmail->id);

                if (!$record) {
                    throw new Exception(Craft::t('sprout-email', 'No Campaign Email with id {id} was found.', [
                        'id' => $campaignType->id
                    ]));
                }

                $record->dateSent = DateTimeHelper::currentUTCDateTime();
                $record->save();
            }

            return $response;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $emailId
     * @param $campaignTypeId
     *
     * @return ModalResponse
     * @throws Exception
     */
    public function getPrepareModal($emailId, $campaignTypeId): ModalResponse
    {
        /**
         * @var $campaignEmail CampaignEmail
         */
        $campaignEmail = SproutEmail::$app->campaignEmails->getCampaignEmailById($emailId);
        $campaignType = SproutEmail::$app->campaignTypes->getCampaignTypeById($campaignTypeId);

        $mailer = $campaignEmail->getMailer();

        $response = new ModalResponse();

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