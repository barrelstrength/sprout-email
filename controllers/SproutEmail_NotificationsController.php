<?php
namespace Craft;

/**
 * Class SproutEmail_NotificationsController
 *
 * @package Craft
 */
class SproutEmail_NotificationsController extends SproutEmail_CampaignController
{
	/**
	 * Renders the notification settings template with passed in route variables
	 *
	 * @param array $variables
	 *
	 * @throws HttpException
	 */
	public function actionNotificationSettingsTemplate(array $variables = array())
	{
		if(!sproutEmail()->checkPermission()) $this->redirect('sproutemail');

		if (isset($variables['campaignId']))
		{
			if (!isset($variables['campaign']))
			{
				$variables['campaign'] = sproutEmail()->campaigns->getCampaignById($variables['campaignId']);
			}
		}
		else
		{
			$variables['campaign'] = new SproutEmail_CampaignModel();
		}

		$variables['isMailerInstalled'] =  (bool) sproutEmail()->mailers->isInstalled('defaultmailer');

		$this->renderTemplate('sproutemail/settings/notifications/_edit', $variables);
	}
}
