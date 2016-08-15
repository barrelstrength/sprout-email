<?php
namespace Craft;

class SproutEmail_CampaignTypeController extends BaseController
{
	/**
	 * Renders a Campaign Type settings template
	 *
	 * @param array $variables
	 */
	public function actionCampaignSettingsTemplate(array $variables = array())
	{
		$campaignTypeId = isset($variables['campaignTypeId']) ? $variables['campaignTypeId'] : null;
		$campaignType   = isset($variables['campaignType']) ? $variables['campaignType'] : null;

		if ($campaignTypeId)
		{
			// If campaign already exists, we're returning an error object
			if (!$campaignType)
			{
				$campaignType = sproutEmail()->campaignTypes->getCampaignTypeById($campaignTypeId);
			}
		}
		else
		{
			$campaignType = new SproutEmail_CampaignTypeModel();
		}

		// Load our template
		$this->renderTemplate('sproutemail/settings/campaigntypes/_edit', array(
			'campaignTypeId' => $campaignTypeId,
			'campaignType'   => $campaignType
		));
	}

	/**
	 * Save campaign type
	 *
	 * @return void
	 */
	public function actionSaveCampaignType()
	{
		$this->requirePostRequest();

		$campaignTypeId = craft()->request->getRequiredPost('campaignTypeId');
		$campaignType   = sproutEmail()->campaignTypes->getCampaignTypeById($campaignTypeId);

		$campaignType->setAttributes(craft()->request->getPost('sproutEmail'));

		if (craft()->request->getPost('saveAsNew'))
		{
			$campaignType->saveAsNew = true;
			$campaignType->id        = null;
			$_POST['redirect']       = 'sproutemail/settings/campaigntypes/edit/{id}';
		}

		if (craft()->request->getPost('fieldLayout'))
		{
			// Set the field layout
			$fieldLayout = craft()->fields->assembleLayoutFromPost();

			$fieldLayout->type = 'SproutEmail_CampaignEmail';
			$campaignType->setFieldLayout($fieldLayout);
		}

		if ($campaignType = sproutEmail()->campaignTypes->saveCampaignType($campaignType))
		{
			craft()->userSession->setNotice(Craft::t('Campaign saved.'));

			$_POST['redirect'] = str_replace('{id}', $campaignType->id, $_POST['redirect']);

			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Unable to save campaign.'));

			craft()->urlManager->setRouteVariables(array(
				'campaignType' => $campaignType
			));
		}
	}

	/**
	 * Delete Campaign Type
	 *
	 * @return void
	 */
	public function actionDeleteCampaignType()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$campaignTypeId = craft()->request->getRequiredPost('campaignTypeId');

		if ($result = sproutEmail()->campaignTypes->deleteCampaignType($campaignTypeId))
		{
			craft()->userSession->setNotice(Craft::t('Campaign Type deleted.'));

			$this->returnJson(array(
				'success' => true
			));
		}
		else
		{
			craft()->userSession->setError(Craft::t("Couldn't delete Campaign."));

			$this->returnJson(array(
				'success' => false
			));
		}
	}
}
