<?php

namespace barrelstrength\sproutemail\controllers;

use craft\mail\Message;
use barrelstrength\sproutbase\app\email\models\Response;
use barrelstrength\sproutbase\SproutBase;
use barrelstrength\sproutemail\elements\SentEmail;
use barrelstrength\sproutemail\SproutEmail;
use craft\helpers\Json;
use craft\web\Controller;
use Craft;
use yii\base\Exception;

class SentEmailController extends Controller
{
    /**
     * Returns info for the Sent Email Resend modal
     *
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionGetResendModal()
    {
        $this->requirePostRequest();

        $emailId = Craft::$app->getRequest()->getBodyParam('emailId');
        $sentEmail = Craft::$app->elements->getElementById($emailId, SentEmail::class);

        $content = Craft::$app->getView()->renderTemplate(
            'sprout-base-email/_modals/sentemails/prepare-resend-email', [
            'sentEmail' => $sentEmail
        ]);

        $response = new Response();
        $response->content = $content;
        $response->success = true;

        return $this->asJson($response->getAttributes());
    }

    /**
     * Re-sends a Sent Email
     *
     * @todo - update to use new EmailElement::getRecipients() syntax
     *
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionResendEmail()
    {
        $this->requirePostRequest();

        $emailId = Craft::$app->request->getBodyParam('emailId');
        /**
         * @var $sentEmail SentEmail
         */
        $sentEmail = Craft::$app->elements->getElementById($emailId, SentEmail::class);

        $recipients = [];

        if (Craft::$app->getRequest()->getBodyParam('recipients') !== null) {
            $recipients = Craft::$app->request->getBodyParam('recipients');

            $result = $this->getValidAndInvalidRecipients($recipients);

            $invalidRecipients = $result['invalid'];
            $validRecipients = $result['valid'];

            $recipients = $validRecipients;

            if (!empty($invalidRecipients)) {
                $invalidEmails = implode(', ', $invalidRecipients);

                $message = Craft::t('sprout-email', "Recipient email addresses do not validate: $invalidEmails");

                $response = Response::createErrorModalResponse(
                    'sprout-base-email/_modals/response',
                    [
                        'email' => $sentEmail,
                        'message' => Craft::t('sprout-email', $message),
                    ]
                );

                return $this->asJson($response);
            }
        } else {
            $recipients[] = $sentEmail->toEmail;
        }

        try {
            $processedRecipients = [];
            $failedRecipients = [];

            if (empty($validRecipients)) {
                throw new Exception(Craft::t('sprout-email', 'No valid recipients.'));
            }

            foreach ($validRecipients as $validRecipient) {
                $recipientEmail = $validRecipient->email;

                $email = new Message();
                $email->setSubject($sentEmail->title);
                $email->setFrom([$sentEmail->fromEmail => $sentEmail->fromName]);
                $email->setTo($recipientEmail);
                $email->setTextBody($sentEmail->body);
                $email->setHtmlBody($sentEmail->htmlBody);

                $infoTable = SproutEmail::$app->sentEmails->createInfoTableModel('sprout-email', [
                    'emailType' => 'Resent Email',
                    'deliveryType' => 'Live'
                ]);

                $variables = [
                    'email' => $sentEmail,
                    'renderedEmail' => $email,
                    'recipients' => $recipients,
                    'processedRecipients' => null,
                    'info' => $infoTable
                ];

                $email->variables = $variables;
                $mailer = Craft::$app->getMailer();

                if ($mailer->send($email)) {
                    $processedRecipients[] = $recipientEmail;
                } else {
                    $failedRecipients[] = $recipientEmail;
                }
            }

            if (!empty($failedRecipients)) {
                $failedRecipientsText = implode(', ', $failedRecipients);

                $message = Craft::t('sprout-email', "Failed to resend emails: $failedRecipientsText");

                throw new Exception($message);
            }

            if (!empty($processedRecipients)) {
                $message = 'Email sent successfully.';

                $response = Response::createModalResponse(
                    'sprout-base-email/_modals/response',
                    [
                        'email' => $sentEmail,
                        'message' => Craft::t('sprout-email', $message)
                    ]
                );

                return $this->asJson($response);
            }

            return true;
        } catch (\Exception $e) {
            $response = Response::createErrorModalResponse(
                'sprout-base-email/_modals/response',
                [
                    'email' => $sentEmail,
                    'message' => Craft::t('sprout-email', $e->getMessage()),
                ]
            );

            return $this->asJson($response);
        }
    }

    /**
     * Get HTML for Info Table HUD
     *
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionGetInfoHtml()
    {
        $this->requirePostRequest();

        $sentEmailId = Craft::$app->getRequest()->getBodyParam('sentEmailId');

        $sentEmail = Craft::$app->elements->getElementById($sentEmailId, SentEmail::class);

        $html = Craft::$app->getView()->renderTemplate('sprout-base-email/sentemails/_hud', [
            'sentEmail' => $sentEmail
        ]);

        $response = [
            'html' => $html
        ];

        return $this->asJson(Json::encode($response));
    }
}
