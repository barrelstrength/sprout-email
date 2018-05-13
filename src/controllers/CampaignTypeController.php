<?php

namespace barrelstrength\sproutemail\controllers;

use barrelstrength\sproutbase\app\email\base\Mailer;
use barrelstrength\sproutbase\app\email\mailers\DefaultMailer;
use barrelstrength\sproutbase\SproutBase;
use barrelstrength\sproutemail\elements\CampaignEmail;
use barrelstrength\sproutemail\models\CampaignType;
use barrelstrength\sproutemail\SproutEmail;
use craft\web\Controller;
use Craft;

class CampaignTypeController extends Controller
{
    /**
     * Renders a Campaign Type settings template
     *
     * @param                        $campaignTypeId
     * @param CampaignType|null      $campaignType
     *
     * @return \yii\web\Response
     */
    public function actionCampaignSettings($campaignTypeId, CampaignType $campaignType = null)
    {
        if ($campaignTypeId) {
            if (!$campaignType) {
                $campaignType = SproutEmail::$app->campaignTypes->getCampaignTypeById($campaignTypeId);
            }
        } else {
            $campaignType = new CampaignType();
        }

        $mailerOptions = [];

        $mailers = SproutBase::$app->mailers->getMailers();

        if (!empty($mailers)) {
            foreach ($mailers as $key => $mailer) {
                /**
                 * @var $mailer Mailer
                 */
                $mailerOptions[$key]['value'] = get_class($mailer);
                $mailerOptions[$key]['label'] = $mailer->getName();
            }
        }

        // Disable default mailer on campaign emails
        unset($mailerOptions[DefaultMailer::class]);

        // Load our template
        return $this->renderTemplate('sprout-base-email/settings/campaigntypes/_edit', [
            'mailers' => $mailerOptions,
            'campaignTypeId' => $campaignTypeId,
            'campaignType' => $campaignType
        ]);
    }

    /**
     * Saves a Campaign Type
     *
     * @throws \Exception
     * @throws \craft\errors\SiteNotFoundException
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionSaveCampaignType()
    {
        $this->requirePostRequest();

        $campaignTypeId = Craft::$app->getRequest()->getBodyParam('campaignTypeId');
        $campaignType = SproutEmail::$app->campaignTypes->getCampaignTypeById($campaignTypeId);

        $campaignType->setAttributes(Craft::$app->getRequest()->getBodyParam('sproutEmail'), false);

        /**
         * @var $mailer Mailer
         */
        $mailer = $campaignType->getMailer();

        if ($mailer) {
            $mailer->prepareSave($campaignType);
        }

        // Set the field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();

        $fieldLayout->type = CampaignEmail::class;

        $campaignType->setFieldLayout($fieldLayout);

        $session = Craft::$app->getSession();

        if ($session AND SproutEmail::$app->campaignTypes->saveCampaignType($campaignType)) {
            $session->setNotice(Craft::t('sprout-email', 'Campaign saved.'));

            $_POST['redirect'] = str_replace('{id}', $campaignType->id, $_POST['redirect']);

            $this->redirectToPostedUrl();
        } else {
            $session->setError(Craft::t('sprout-email', 'Unable to save campaign.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'campaignType' => $campaignType
            ]);
        }
    }

    /**
     * Deletes a Campaign Type
     *
     * @return \yii\web\Response
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionDeleteCampaignType()
    {
        $this->requirePostRequest();

        $campaignTypeId = Craft::$app->getRequest()->getBodyParam('id');

        $session = Craft::$app->getSession();

        if ($session AND $result = SproutEmail::$app->campaignTypes->deleteCampaignType($campaignTypeId)) {
            $session->setNotice(Craft::t('sprout-email', 'Campaign Type deleted.'));

            return $this->asJson([
                'success' => true
            ]);
        }

        $session->setError(Craft::t('sprout-email', "Couldn't delete Campaign."));

        return $this->asJson([
            'success' => false
        ]);
    }
}
