<?php
namespace Craft;

class SproutEmail_CampaignController extends BaseController
{
	/**
	 * Save campaign
	 *
	 * @return void
	 */
	public function actionSaveCampaign()
	{
		$this->requirePostRequest();

		$campaignId = craft()->request->getRequiredPost('sproutEmail.id');
		$campaign   = sproutEmail()->campaigns->getCampaignById($campaignId);

		$campaign->setAttributes(craft()->request->getPost('sproutEmail'));

		if (craft()->request->getPost('fieldLayout'))
		{
			// Set the field layout
			$fieldLayout = craft()->fields->assembleLayoutFromPost();

			$fieldLayout->type = 'SproutEmail_Campaign';
			$campaign->setFieldLayout($fieldLayout);
		}

		$tab = craft()->request->getPost('tab');

		if (($campaign = sproutEmail()->campaigns->saveCampaign($campaign, $tab)) && !$campaign->hasErrors())
		{
			craft()->userSession->setNotice(Craft::t('Campaign successfully saved.'));
			$this->redirectToPostedUrl($campaign);
			craft()->end();
		}

		craft()->userSession->setError(Craft::t('Unable to save campaign.'));

		craft()->urlManager->setRouteVariables(
			array(
				'campaign' => $campaign
			)
		);
	}

	/**
	 * Delete campaign
	 *
	 * @return void
	 */
	public function actionDeleteCampaign()
	{
		$this->requirePostRequest();

		$campaignId = craft()->request->getRequiredPost('id');

		// @TODO - handle errors
		if (craft()->sproutEmail_campaign->deleteCampaign($campaignId))
		{
			craft()->userSession->setNotice(Craft::t('Campaign deleted.'));

			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t delete Campaign.'));
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
			$service = 'sproutEmail_'.lcfirst($provider);
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
				$variables['campaign'] = sproutEmail()->campaigns->getCampaignById($variables['campaignId']);
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
