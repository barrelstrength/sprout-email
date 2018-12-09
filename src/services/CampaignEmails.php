<?php

namespace barrelstrength\sproutemail\services;

use barrelstrength\sproutbase\app\email\base\CampaignEmailSenderInterface;
use barrelstrength\sproutbase\app\email\base\Mailer;
use barrelstrength\sproutemail\elements\CampaignEmail;
use barrelstrength\sproutemail\models\CampaignType;
use barrelstrength\sproutemail\records\CampaignEmail as CampaignEmailRecord;
use craft\base\Component;
use Craft;
use craft\helpers\DateTimeHelper;
use yii\base\Exception;

/**
 * Class CampaignEmails
 *
 * @package barrelstrength\sproutemail\services
 */
class CampaignEmails extends Component
{
    const EVENT_SEND_SPROUTEMAIL = 'onSendSproutEmail';

    public $saveAsNew;

    /**
     * @param CampaignEmail $campaignEmail
     * @param CampaignType  $campaignType
     *
     * @return CampaignEmail|bool
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\base\Exception
     */
    public function saveCampaignEmail(CampaignEmail $campaignEmail, CampaignType $campaignType)
    {
        $campaignEmailRecord = new CampaignEmailRecord();

        if ($campaignEmail->id && !$campaignEmail->saveAsNew) {
            $campaignEmailRecord = CampaignEmailRecord::findOne($campaignEmail->id);

            if (!$campaignEmailRecord) {
                throw new Exception(Craft::t('sprout-email', 'No entry exists with the ID “{id}”', ['id' => $campaignEmail->id]));
            }
        }

        $campaignEmailRecord->campaignTypeId = $campaignEmail->campaignTypeId;

        if ($campaignType->titleFormat) {
            $renderedSubject = Craft::$app->getView()->renderObjectTemplate($campaignType->titleFormat, $campaignEmail);

            $campaignEmail->title = $renderedSubject;
            $campaignEmail->subjectLine = $renderedSubject;
            $campaignEmailRecord->subjectLine = $renderedSubject;
        } else {
            $campaignEmail->title = $campaignEmail->subjectLine;
            $campaignEmailRecord->subjectLine = $campaignEmail->subjectLine;
        }

        $mailer = $campaignType->getMailer();

        if ($mailer) {
            // Give the Mailer a chance to prep the settings from post
            $preppedSettings = $mailer->prepListSettings($campaignEmail->listSettings);

            // Set the prepped settings on the FieldRecord, FieldModel, and the field type
            $campaignEmailRecord->listSettings = $preppedSettings;

            /** @var Mailer $mailer */
            $mailer->emailElement = $campaignEmail;

            // Run mailer validation so Mailers have a chance to modify the Campaign Email Element
            // We use this in the Copy/Paste Mailer to ensure the Sender settings validate.
            $mailer->validate();

            $campaignEmail = $mailer->emailElement;
        }

        $campaignEmailRecord->setAttributes($campaignEmail->getAttributes());

        $campaignEmailRecord->validate();

        if ($campaignEmail->saveAsNew) {
            // Prevent subjectLine to be appended by a number
            $campaignEmailRecord->subjectLine = $campaignEmail->subjectLine;

            $campaignEmail->title = $campaignEmail->subjectLine;
        }

        $campaignEmail->addErrors($campaignEmailRecord->getErrors());

        if (!$campaignEmail->hasErrors()) {
            try {
                if (Craft::$app->getElements()->saveElement($campaignEmail)) {
                    return $campaignEmail;
                }
            } catch (\Exception $e) {
                throw $e;
            }
        }

        return false;
    }

    /**
     * @param $id
     *
     * @return CampaignEmail|null
     */
    public function getCampaignEmailById($id)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Craft::$app->getElements()->getElementById($id, CampaignEmail::class);
    }

    /**
     * @param       $campaignEmail
     * @param array $values
     *
     * @throws \yii\db\Exception
     */
    public function saveEmailSettings($campaignEmail, array $values = [])
    {
        if ($campaignEmail->id != null) {
            $campaignEmailRecord = CampaignEmailRecord::findOne($campaignEmail->id);

            if ($campaignEmailRecord) {
                $transaction = Craft::$app->getDb()->beginTransaction();

                $campaignEmailRecord->emailSettings = $values;

                if ($campaignEmailRecord->save(false)) {
                    $transaction->commit();
                }
            }
        }
    }

    /**
     * @param        $htmlBody
     * @param        $body
     * @param string $fileExtension
     *
     * @throws \yii\base\ExitException
     */
    public function showCampaignEmail($htmlBody, $body, $fileExtension = 'html')
    {
        if ($fileExtension == 'txt') {
            $output = $body;
        } else {
            $output = $htmlBody;
        }

        // Output it into a buffer, in case TasksService wants to close the connection prematurely
        ob_start();

        echo $output;

        // End the request
        Craft::$app->end();
    }

    /**
     * @param CampaignEmail $campaignEmail
     *
     * @return mixed
     * @throws \Exception
     */
    public function sendCampaignEmail(CampaignEmail $campaignEmail)
    {
        /* @var $mailer Mailer|CampaignEmailSenderInterface */
        $mailer = $campaignEmail->getCampaignType()->getMailer();

        if (!$mailer) {
            throw new Exception(Craft::t('sprout-email', 'No mailer with id {id} was found.', [
                'id' => $campaignEmail->getMailer()->id
            ]));
        }

        /**
         * @var $mailer Mailer
         */
        try {
            $response = $mailer->sendCampaignEmail($campaignEmail);

            if ($response) {
                // Update dateSent to change mark status
                $record = CampaignEmailRecord::findOne($campaignEmail->id);

                if (!$record) {
                    throw new Exception(Craft::t('sprout-email', 'No Campaign Email with id {id} was found.', [
                        'id' => $campaignEmail->getCampaignType()->id
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
}
