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
		$campaign = sproutEmail()->campaigns->getCampaignById($campaignId);

		$campaign->setAttributes(craft()->request->getPost('sproutEmail'));

		if (craft()->request->getPost('saveAsNew'))
		{
			$campaign->saveAsNew = true;
			$campaign->id = null;
		}

		if (craft()->request->getPost('fieldLayout'))
		{
			// Set the field layout
			$fieldLayout = craft()->fields->assembleLayoutFromPost();

			$fieldLayout->type = 'SproutEmail_Campaign';
			$campaign->setFieldLayout($fieldLayout);
		}

		if (($campaign = sproutEmail()->campaigns->saveCampaign($campaign)) && !$campaign->hasErrors())
		{
			craft()->userSession->setNotice(Craft::t('Campaign successfully saved.'));

			if (sproutEmail()->notifications->getNotificationEntryByCampaignId($campaign->id) == false)
			{
				$entry = sproutEmail()->entries->saveRelatedEntry($campaign);
			}
			else
			{
				$entry = sproutEmail()->notifications->getNotificationEntryByCampaignId($campaign->id);
			}

			// Pass entryId for save and edit notification button
			$campaign->entryId = $entry->id;

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
		$this->requireAjaxRequest();

		$campaignId = craft()->request->getRequiredPost('id');

		if ($result = craft()->sproutEmail_campaigns->deleteCampaign($campaignId))
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
