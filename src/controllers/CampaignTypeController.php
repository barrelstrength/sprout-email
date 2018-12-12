<?php

namespace barrelstrength\sproutemail\controllers;

use barrelstrength\sproutbase\app\email\base\Mailer;
use barrelstrength\sproutbase\app\email\mailers\DefaultMailer;
use barrelstrength\sproutbase\SproutBase;
use barrelstrength\sproutemail\elements\CampaignEmail;
use barrelstrength\sproutemail\models\CampaignType;
use barrelstrength\sproutemail\SproutEmail;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use Craft;
use yii\web\Response;

class CampaignTypeController extends Controller
{
    /**
     * Renders a Campaign Type settings template
     * @param                   $campaignTypeId
     * @param CampaignType|null $campaignType
     *
     * @return Response
     * @throws \Exception
     */
    public function actionCampaignSettings($campaignTypeId, CampaignType $campaignType = null): Response
    {
        if ($campaignTypeId && $campaignType === null) {

            if ($campaignTypeId == 'new') {
                $campaignType = new CampaignType();
            } else {
                $campaignType = SproutEmail::$app->campaignTypes->getCampaignTypeById($campaignTypeId);

                if ($campaignType->id == null) {
                    throw new \Exception("Invalid campaign type id");
                }
            }
        }

        $mailerOptions = [];

        $mailers = SproutBase::$app->mailers->getMailers();

        if (!empty($mailers)) {
            foreach ($mailers as $key => $mailer) {
                /**
                 * @var $mailer Mailer
                 */
                $mailerOptions[$key]['value'] = get_class($mailer);
                $mailerOptions[$key]['label'] = $mailer::displayName();
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

        // Set the field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();

        $fieldLayout->type = CampaignEmail::class;

        $campaignType->setFieldLayout($fieldLayout);

        $session = Craft::$app->getSession();

        if ($session AND SproutEmail::$app->campaignTypes->saveCampaignType($campaignType)) {
            $session->setNotice(Craft::t('sprout-email', 'Campaign saved.'));

            $url = UrlHelper::cpUrl("sprout-email/settings/campaigntypes/edit/" . $campaignType->id);
            return $this->redirect($url);
        } else {
            $session->setError(Craft::t('sprout-email', 'Unable to save campaign.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'campaignType' => $campaignType
            ]);
        }

        return null;
    }

    /**
     * Deletes a Campaign Type
     *
     * @return Response
     * @throws \craft\errors\MissingComponentException
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionDeleteCampaignType(): Response
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
