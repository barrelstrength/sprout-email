<?php

namespace barrelstrength\sproutemail\controllers;

use barrelstrength\sproutbase\app\email\models\SimpleRecipient;
use barrelstrength\sproutbase\app\email\models\SimpleRecipientList;
use craft\mail\Message;
use barrelstrength\sproutbase\app\email\models\Response;
use barrelstrength\sproutemail\elements\SentEmail;
use barrelstrength\sproutemail\SproutEmail;
use craft\helpers\Json;
use craft\web\Controller;
use Craft;
use yii\base\Exception;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;

class SentEmailController extends Controller
{
    /**
     * Re-sends a Sent Email
     *
     * @todo - update to use new EmailElement::getRecipients() syntax
     *
     * @return bool|\yii\web\Response
     * @throws Exception
     * @throws \Throwable
     * @throws \Twig_Error_Loader
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionResendEmail()
    {
        // @todo - Why is this so long?
        // @todo - do we really need all this logic here? Can we delegate it to a common sendEmail method?

        $this->requirePostRequest();

        $emailId = Craft::$app->request->getBodyParam('emailId');
        /**
         * @var $sentEmail SentEmail
         */
        $sentEmail = Craft::$app->elements->getElementById($emailId, SentEmail::class);

        $recipients = [];

        if (Craft::$app->getRequest()->getBodyParam('recipients') !== null) {
            $recipients = Craft::$app->request->getBodyParam('recipients');

            $validator = new EmailValidator();
            $validations = new MultipleValidationWithAnd([
                new RFCValidation()
            ]);
            $recipientList = new SimpleRecipientList();
            $recipientArray = explode(',', $recipients);

            foreach ($recipientArray as $recipient) {
                $recipientModel = new SimpleRecipient();
                $recipientModel->email = trim($recipient);

                if ($validator->isValid($recipientModel->email, $validations)) {
                    $recipientList->addRecipient($recipientModel);
                } else {
                    $recipientList->addInvalidRecipient($recipientModel);
                }
            }

            if ($recipientList->getInvalidRecipients()) {
                $invalidEmails = [];
                foreach ($recipientList->getInvalidRecipients() as $invalidRecipient) {
                    $invalidEmails[] = $invalidRecipient->email;
                }

                return $this->asJson(
                    Response::createErrorModalResponse('sprout-base-email/_modals/response', [
                        'email' => $sentEmail,
                        'message' => Craft::t('sprout-base', 'Recipient email addresses do not validate: {invalidEmails}', [
                            'invalidEmails' => implode(', ', $invalidEmails)
                        ])
                    ])
                );
            }

            $validRecipients = $recipientList->getRecipients();
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
     * Get HTML for Info Table HUD
     *
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionGetInfoHtml()
    {
        $this->requirePostRequest();

        $sentEmailId = Craft::$app->getRequest()->getBodyParam('emailId');
        $sentEmail = Craft::$app->elements->getElementById($sentEmailId, SentEmail::class);

        $content = Craft::$app->getView()->renderTemplate(
            'sprout-base-email/_modals/sentemails/info-table', [
            'info' => $sentEmail->getInfo()
        ]);

        $response = new Response();
        $response->content = $content;
        $response->success = true;

        return $this->asJson($response->getAttributes());
    }
}
