<?php
namespace Craft;

class SproutEmail_CampaignTypeController extends BaseController
{
	/**
	 * Save campaign type
	 *
	 * @return void
	 */
	public function actionSaveCampaignType()
	{
		$this->requirePostRequest();

		$campaignId = craft()->request->getRequiredPost('sproutEmail.id');
		$campaign   = sproutEmail()->campaignTypes->getCampaignTypeById($campaignId);

		$campaign->setAttributes(craft()->request->getPost('sproutEmail'));

		if (craft()->request->getPost('saveAsNew'))
		{
			$campaign->saveAsNew = true;
			$campaign->id        = null;
			$_POST['redirect']   = 'sproutemail/settings/campaigns/edit/{id}';
		}

		if (craft()->request->getPost('fieldLayout'))
		{
			// Set the field layout
			$fieldLayout = craft()->fields->assembleLayoutFromPost();

			$fieldLayout->type = 'SproutEmail_Campaign';
			$campaign->setFieldLayout($fieldLayout);
		}

		if ($campaign = sproutEmail()->campaignTypes->saveCampaignType($campaign))
		{
			craft()->userSession->setNotice(Craft::t('Campaign saved.'));

			$_POST['redirect'] = str_replace('{id}', $campaign->id, $_POST['redirect']);

			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Unable to save campaign.'));

			craft()->urlManager->setRouteVariables(array(
				'campaign' => $campaign
			));
		}
	}

	/**
	 * Delete campaign
	 *
	 * @return void
	 */
	public function actionDeleteCampaignType()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$campaignId = craft()->request->getRequiredPost('id');

		if ($result = sproutEmail()->campaignTypes->deleteCampaignType($campaignId))
		{
			craft()->userSession->setNotice(Craft::t('Campaign deleted.'));

			$this->returnJson(array(
				'success' => true
			));
		}
		else
		{
			craft()->userSession->setError(Craft::t("Couldn’t delete Campaign."));

			$this->returnJson(array(
				'success' => false
			));
		}
	}

	/**
	 * Save settings
	 *
	 * @return void
	 */
	public function actionSaveSettings()
	{
		$this->requirePostRequest();

		foreach (craft()->request->getPost('settings') as $provider => $settings)
		{
			$service = 'sproutEmail_' . lcfirst($provider);
			craft()->$service->saveSettings($settings);
		}

		craft()->userSession->setNotice(Craft::t('Settings successfully saved.'));
		$this->redirectToPostedUrl();
	}

	public function actionCampaignSettingsTemplate(array $variables = array())
	{
		if (isset($variables['campaignId']))
		{
			// If campaign already exists, we're returning an error object
			if (!isset($variables['campaign']))
			{
				$variables['campaign'] = sproutEmail()->campaignTypes->getCampaignTypeById($variables['campaignId']);
			}
		}
		else
		{
			$variables['campaign'] = new SproutEmail_Campaign();
		}

		// Load our template
		$this->renderTemplate('sproutemail/settings/campaigns/_edit', $variables);
	}
}
