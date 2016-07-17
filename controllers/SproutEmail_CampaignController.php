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
			$_POST['redirect'] = 'sproutemail/settings/campaigns/edit/{id}';
		}

		if (craft()->request->getPost('fieldLayout'))
		{
			// Set the field layout
			$fieldLayout = craft()->fields->assembleLayoutFromPost();

			$fieldLayout->type = 'SproutEmail_Campaign';
			$campaign->setFieldLayout($fieldLayout);
		}

		if ($campaign = sproutEmail()->campaigns->saveCampaign($campaign))
		{
			craft()->userSession->setNotice(Craft::t('Campaign saved.'));

			// Create the related Email Entry if we have a notification
			if ($campaign->type == 'notification')
			{
				if (sproutEmail()->notificationEmails->getNotificationEmailByCampaignId($campaign->id) == false)
				{
					$campaignEmail = sproutEmail()->campaignEmails->saveRelatedCampaignEmail($campaign);
				}
				else
				{
					$campaignEmail = sproutEmail()->notificationEmails->getNotificationEmailByCampaignId($campaign->id);
				}

				// Pass emailId for save and edit notification button
				$campaign->emailId = $campaignEmail->id;

				$this->redirectToPostedUrl($campaign);
				craft()->end();
			}

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
