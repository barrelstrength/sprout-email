<?php

namespace barrelstrength\sproutemail\controllers;

use barrelstrength\sproutbaseemail\models\ModalResponse;
use barrelstrength\sproutbaseemail\models\SimpleRecipient;
use barrelstrength\sproutbaseemail\models\SimpleRecipientList;
use barrelstrength\sproutemail\elements\SentEmail;
use barrelstrength\sproutemail\services\SentEmails;
use barrelstrength\sproutemail\SproutEmail;
use Craft;
use craft\mail\Mailer;
use craft\mail\Message;
use craft\web\Controller;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Exception;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class SentEmailController extends Controller
{
    /**
     * Re-sends a Sent Email
     *
     * @return bool|Response
     * @throws Exception
     * @throws Throwable
     * @throws BadRequestHttpException
     * @todo - update to use new EmailElement::getRecipients() syntax
     *
     */
    public function actionResendEmail()
    {
        $this->requirePostRequest();
        $this->requirePermission('sproutEmail-resendEmails');

        $emailId = Craft::$app->request->getBodyParam('emailId');
        /**
         * @var $sentEmail SentEmail
         */
        $sentEmail = Craft::$app->elements->getElementById($emailId, SentEmail::class);

        $recipients = Craft::$app->getRequest()->getBodyParam('recipients');

        if (!$recipients) {
            return $this->asJson(
                ModalResponse::createErrorModalResponse('sprout-base-email/_modals/response', [
                    'email' => $sentEmail,
                    'message' => Craft::t('sprout-email', 'A recipient email address is required')
                ])
            );
        }

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
                ModalResponse::createErrorModalResponse('sprout-base-email/_modals/response', [
                    'email' => $sentEmail,
                    'message' => Craft::t('sprout-email', 'Invalid email address(es) provided: {invalidEmails}', [
                        'invalidEmails' => implode(', ', $invalidEmails)
                    ])
                ])
            );
        }

        $validRecipients = $recipientList->getRecipients();

        try {
            $processedRecipients = [];
            $failedRecipients = [];

            if (empty($validRecipients)) {
                throw new Exception('No valid recipients.');
            }

            foreach ($validRecipients as $validRecipient) {
                $recipientEmail = $validRecipient->email;

                $email = new Message();
                $email->setSubject($sentEmail->title);
                $email->setFrom([$sentEmail->fromEmail => $sentEmail->fromName]);
                $email->setTo($recipientEmail);
                $email->setTextBody($sentEmail->body);
                $email->setHtmlBody($sentEmail->htmlBody);

                $infoTable = SproutEmail::$app->sentEmails->createInfoTableModel('sprout-email');

                $emailTypes = $infoTable->getEmailTypes();
                $infoTable->emailType = $emailTypes['Resent'];

                $deliveryTypes = $infoTable->getDeliveryTypes();
                $infoTable->deliveryType = $deliveryTypes['Live'];

                $mailer = Craft::$app->getMailer();
                $email->mailer = new Mailer();

                $variables = [
                    'email' => $sentEmail,
                    'renderedEmail' => $email,
                    'recipients' => $recipients,
                    'processedRecipients' => null,
                    SentEmails::SENT_EMAIL_MESSAGE_VARIABLE => $infoTable
                ];

                $email->variables = $variables;

                if ($mailer->send($email)) {
                    $processedRecipients[] = $recipientEmail;
                } else {
                    $failedRecipients[] = $recipientEmail;
                }
            }

            if (!empty($failedRecipients)) {
                $failedRecipientsText = implode(', ', $failedRecipients);

                throw new Exception('Failed to resend emails: '.$failedRecipientsText);
            }

            if (!empty($processedRecipients)) {
                $response = ModalResponse::createModalResponse(
                    'sprout-base-email/_modals/response',
                    [
                        'email' => $sentEmail,
                        'message' => Craft::t('sprout-email', 'Email sent successfully.')
                    ]
                );

                return $this->asJson($response);
            }

            return true;
        } catch (\Exception $e) {
            $response = ModalResponse::createErrorModalResponse(
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
     * @return Response
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     */
    public function actionGetResendModal(): Response
    {
        $this->requirePostRequest();
        $this->requirePermission('sproutEmail-resendEmails');

        $emailId = Craft::$app->getRequest()->getBodyParam('emailId');
        $sentEmail = Craft::$app->elements->getElementById($emailId, SentEmail::class);

        $content = Craft::$app->getView()->renderTemplate(
            'sprout-base-email/_modals/sentemails/prepare-resend-email', [
            'sentEmail' => $sentEmail
        ]);

        $response = new ModalResponse();
        $response->content = $content;
        $response->success = true;

        return $this->asJson($response->getAttributes());
    }

    /**
     * Get HTML for Info Table HUD
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws Exception
     * @throws ForbiddenHttpException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function actionGetInfoHtml(): Response
    {
        $this->requirePostRequest();
        $this->requirePermission('sproutEmail-viewSentEmail');

        $sentEmailId = Craft::$app->getRequest()->getBodyParam('emailId');

        /** @var $sentEmail SentEmail */
        $sentEmail = Craft::$app->elements->getElementById($sentEmailId, SentEmail::class);

        $content = Craft::$app->getView()->renderTemplate(
            'sprout-base-email/_modals/sentemails/info-table', [
            'info' => $sentEmail->getInfo()
        ]);

        $response = new ModalResponse();
        $response->content = $content;
        $response->success = true;

        return $this->asJson($response->getAttributes());
    }
}
