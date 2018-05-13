<?php

namespace barrelstrength\sproutemail\controllers;

use barrelstrength\sproutbase\SproutBase;
use barrelstrength\sproutemail\SproutEmail;
use craft\web\Controller;
use Craft;

class MailerController extends Controller
{
    /**
     * Renders the Mailer Edit template
     *
     * @param array $variables
     *
     * @throws \HttpException
     */
    public function actionEditSettingsTemplate(array $variables = [])
    {
        $mailerId = $variables['mailerId'] ?? null;

        if (!$mailerId) {
            throw new \HttpException(404, Craft::t('sprout-email', 'No mailer id was provided'));
        }

        $mailer = SproutBase::$app->mailers->getMailerByName($mailerId);

        if (!$mailer) {
            throw new \HttpException(404, Craft::t('sprout-email', 'No mailer was found with that id'));
        }

        if (!$settings) {
            $settings = $mailer->getSettings();
        }

        $this->renderTemplate('sprout-base-email/settings/mailers/edit', [
            'mailer' => $mailer,
            'settings' => $settings
        ]);
    }

    /**
     * Provides a way for mailers to render content to perform actions inside a a modal window
     *
     * @return \yii\web\Response
     * @throws \Exception
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionGetPrepareModal()
    {
        $this->requirePostRequest();

        $mailer = Craft::$app->getRequest()->getBodyParam('mailer');
        $emailId = Craft::$app->getRequest()->getBodyParam('emailId');
        $campaignTypeId = Craft::$app->getRequest()->getBodyParam('campaignTypeId');

        $modal = SproutEmail::$app->mailers->getPrepareModal($mailer, $emailId, $campaignTypeId);

        return $this->asJson($modal->getAttributes());
    }
}
