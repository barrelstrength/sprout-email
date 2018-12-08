<?php

namespace barrelstrength\sproutemail\controllers;

use barrelstrength\sproutemail\SproutEmail;
use craft\web\Controller;
use Craft;
use yii\web\Response;

class MailerController extends Controller
{
    /**
     * Provides a way for mailers to render content to perform actions inside a a modal window
     *
     * @return Response
     * @throws \Exception
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionGetPrepareModal(): Response
    {
        $this->requirePostRequest();

        $emailId = Craft::$app->getRequest()->getBodyParam('emailId');
        $campaignTypeId = Craft::$app->getRequest()->getBodyParam('campaignTypeId');

        $modal = SproutEmail::$app->mailers->getPrepareModal($emailId, $campaignTypeId);

        return $this->asJson($modal->getAttributes());
    }
}
