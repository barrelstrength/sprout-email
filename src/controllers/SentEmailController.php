<?php

namespace barrelstrength\sproutemail\controllers;

use barrelstrength\sproutbase\app\email\base\EmailTemplateTrait;
use barrelstrength\sproutbase\app\email\models\Message;
use barrelstrength\sproutbase\app\email\models\Response;
use barrelstrength\sproutbase\SproutBase;
use barrelstrength\sproutemail\elements\SentEmail;
use barrelstrength\sproutemail\SproutEmail;
use craft\helpers\Json;
use craft\web\Controller;
use Craft;

class SentEmailController extends Controller
{
    use EmailTemplateTrait;
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

        $emailId   = Craft::$app->getRequest()->getBodyParam('emailId');
        $sentEmail = Craft::$app->elements->getElementById($emailId, SentEmail::class);

        $content = Craft::$app->getView()->renderTemplate(
            'sprout-base-email/_modals/sentemails/prepare-resend-email', array(
            'sentEmail' => $sentEmail
        ));

        $response          = new Response();
        $response->content = $content;
        $response->success = true;

        return $this->asJson($response->getAttributes());
    }

    /**
     * Resends a Sent Email
     *
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionResendEmail()
    {
        $this->requirePostRequest();

        $emailId   = Craft::$app->request->getBodyParam('emailId');
        /**
         * @var $sentEmail SentEmail
         */
        $sentEmail = Craft::$app->elements->getElementById($emailId, SentEmail::class);

        $recipients = array();

        if (Craft::$app->getRequest()->getBodyParam('recipients') !== null)
        {
            $recipients = Craft::$app->request->getBodyParam('recipients');

            $result = $this->getValidAndInvalidRecipients($recipients);

            $invalidRecipients = $result['invalid'];
            $validRecipients   = $result['valid'];

            $recipients = $validRecipients;

            if (!empty($invalidRecipients))
            {
                $invalidEmails = implode(", ", $invalidRecipients);

                $message = Craft::t('sprout-email', "Recipient email addresses do not validate: $invalidEmails");

                $response = Response::createErrorModalResponse(
                    'sprout-base-email/_modals/response',
                    array(
                        'email'   => $sentEmail,
                        'message' => Craft::t('sprout-email', $message),
                    )
                );

                $this->asJson($response);
            }
        }
        else
        {
            $recipients[] = $sentEmail->toEmail;
        }

        try
        {
            $processedRecipients = array();
            $failedRecipients    = array();

            if (!empty($validRecipients))
            {
                foreach ($validRecipients as $validRecipient)
                {
                    $recipientEmail = $validRecipient->email;

                    $email            = new Message();
                    $email->setSubject($sentEmail->title);
                    $email->setFrom([$sentEmail->fromEmail => $sentEmail->fromName]);
                    $email->setTo($recipientEmail);
                    $email->setTextBody($sentEmail->body);
                    $email->setHtmlBody($sentEmail->htmlBody);

                    $infoTable =  SproutEmail::$app->sentEmails->createInfoTableModel('sprout-email', array(
                        'emailType'    => 'Resent Email',
                        'deliveryType' => 'Live'
                    ));

                    $variables = array(
                        'email'               => $sentEmail,
                        'renderedEmail'       => $email,
                        'recipients'          => $recipients,
                        'processedRecipients' => null,
                        'info'                => $infoTable
                    );

                    if (SproutBase::$app->mailers->sendEmail($email, $variables))
                    {
                        $processedRecipients[] = $recipientEmail;
                    }
                    else
                    {
                        $failedRecipients[] = $recipientEmail;
                    }
                }

                if (!empty($failedRecipients))
                {
                    $failedRecipientsText = implode(", ", $failedRecipients);

                    $message = Craft::t('sprout=email', "Failed to resend emails: $failedRecipientsText");

                    throw new \Exception($message);
                }

                if (!empty($processedRecipients))
                {
                    $message = "Email sent successfully.";

                    $response = Response::createModalResponse(
                        'sprout-base-email/_modals/response',
                        array(
                            'email'   => $sentEmail,
                            'message' => Craft::t('sprout-email', $message)
                        )
                    );

                    return $this->asJson($response);
                }
            }
        }
        catch (\Exception $e)
        {
            $response = Response::createErrorModalResponse(
                'sprout-base-email/_modals/response',
                array(
                    'email'   => $sentEmail,
                    'message' => Craft::t('sprout-email', $e->getMessage()),
                )
            );

            $this->asJson($response);
        }
    }

    /**
     * Get HTML for Info Table HUD
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionGetInfoHtml()
    {
        $this->requirePostRequest();

        $sentEmailId = Craft::$app->getRequest()->getBodyParam('sentEmailId');

        $sentEmail = Craft::$app->elements->getElementById($sentEmailId, SentEmail::class);

        $html = Craft::$app->getView()->renderTemplate('sprout-base-email/sentemails/_hud', array(
            'sentEmail' => $sentEmail
        ));

        $response = array(
            'html' => $html
        );

        return $this->asJson(Json::encode($response));
    }
}
