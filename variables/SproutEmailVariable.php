<?php

namespace Craft;

class SproutEmailVariable
{
	/**
	 * @var ElementCriteriaModel
	 */
	public $entries;

	public function __construct()
	{
		/**
		 * craft.sproutEmail.entries
		 */
		$this->entries = craft()->elements->getCriteria('SproutEmail_CampaignEmail');
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		$plugin = craft()->plugins->getPlugin('sproutemail');

		return $plugin->getName();
	}

	/**
	 * @return string
	 */
	public function getVersion()
	{
		$plugin = craft()->plugins->getPlugin('sproutemail');

		return $plugin->getVersion();
	}

	/**
	 * @return string
	 */
	public function getSettings()
	{
		$plugin = craft()->plugins->getPlugin('sproutemail');

		return $plugin->getSettings();
	}

	/**
	 * Returns a list of available mailers
	 *
	 * @return SproutEmailBaseMailer[]
	 */
	public function getMailers()
	{
		return sproutEmail()->mailers->getMailers();
	}

	/**
	 * Returns a list of mailers that can send campaigns
	 *
	 * @todo - update this and remove the $exclude variable in favor of handling exceptions on the Mailer Class
	 *
	 * @return SproutEmailBaseMailer[]
	 */
	public function getCampaignMailers($exclude = null)
	{
		$mailers = $this->getMailers();

		if ($exclude != null)
		{
			unset($mailers[$exclude]);
		}

		return $mailers;
	}

	/**
	 * @param string $mailer
	 *
	 * @return SproutEmailBaseMailer|null
	 */
	public function getMailer($mailer)
	{
		return sproutEmail()->mailers->getMailerByName($mailer);
	}

	/**
	 * @inheritDoc SproutEmail_DynamicService::getAvailableEvents()
	 *
	 * @return SproutEmailBaseEvent|array
	 */
	public function getAvailableEvents()
	{
		return sproutEmail()->notificationEmails->getAvailableEvents();
	}

	/**
	 * @param $event
	 * @param $notificationEmail
	 *
	 * @return mixed
	 */
	public function getEventSelectedOptions($event, $notificationEmail)
	{
		return sproutEmail()->notificationEmails->getEventSelectedOptions($event, $notificationEmail);
	}

	/**
	 * @return SproutEmail_NotificationModel[]|null
	 */
	public function getNotifications()
	{
		return sproutEmail()->notificationEmails->getNotifications();
	}

	/**
	 * @param $id
	 *
	 * @return BaseElementModel|null
	 */
	public function getNotificationEmailById($id)
	{
		return sproutEmail()->notificationEmails->getNotificationEmailById($id);
	}

	/**
	 * @param string $type
	 *
	 * @return mixed Campaign model
	 */
	public function getCampaignTypes()
	{
		return sproutEmail()->campaignTypes->getCampaignTypes();
	}

	/**
	 * Get a Campaign Type by id *
	 *
	 * @param int $campaignTypeId
	 *
	 * @return object campaign record
	 */
	public function getCampaignTypeById($campaignTypeId)
	{
		return sproutEmail()->campaignTypes->getCampaignTypeById($campaignTypeId);
	}

	/**
	 * Get All Sections for Options dropdown *
	 *
	 * @param string $indexBy
	 *
	 * @return array
	 */
	public function getAllSections($indexBy = null)
	{
		$result = craft()->sections->getAllSections($indexBy);

		$options = array();

		foreach ($result as $key => $section)
		{
			array_push(
				$options, array(
					'label' => $section->name,
					'value' => $section->id
				)
			);
		}

		return $options;
	}

	/**
	 * @param string $indexBy
	 *
	 * @return array
	 */
	public function getAllUserGroups($indexBy = null)
	{
		$groups  = craft()->userGroups->getAllGroups($indexBy);
		$options = array();

		foreach ($groups as $group)
		{
			$options[$group->id] = $group->name;
		}

		return $options;
	}

	/**
	 * Get subscriber list for specified provider
	 *
	 * @param string $mailer
	 *
	 * @return array
	 */
	public function getSubscriberList($mailer)
	{
		$mailer = sproutEmail()->mailers->getMailerByName(strtolower($mailer));

		return $mailer->getSubscriberList();
	}

	public function getGeneralSettingsTemplate($emailProvider = null)
	{
		$customTemplate       = 'sproutemail/_providers/' . $emailProvider . '/generalCampaignSettings';
		$customTemplateExists = craft()->templates->doesTemplateExist($customTemplate);

		// if there is a custom set of general settings for this provider, return those; if not, return the default
		if ($customTemplateExists)
		{
			return true;
		}

		return false;
	}

	public function getCampaignEmailById($emailId)
	{
		return sproutEmail()->campaignEmails->getCampaignEmailById($emailId);
	}

	public function getCampaignEmailShareUrl($emailId, $campaignTypeId)
	{
		return UrlHelper::getActionUrl('sproutEmail/campaignEmails/shareCampaignEmail', array(
			'emailId'        => $emailId,
			'campaignTypeId' => $campaignTypeId
		));
	}

	public function getSentEmailById($emailId)
	{
		return sproutEmail()->sentEmails->getSentEmailById($emailId);
	}

	public function doesSiteTemplateExist($template)
	{
		return sproutEmail()->doesSiteTemplateExist($template);
	}

	public function canCreateExamples()
	{
		return sproutEmail()->canCreateExamples();
	}

	public function getTemplatePath()
	{
		return craft()->path->getSiteTemplatesPath();
	}

	public function hasExamples()
	{
		return sproutEmail()->hasExamples();
	}

	public function getMailerBySentEmailId($id)
	{
		return sproutEmail()->sentEmails->getMailerBySentEmailId($id);
	}

	public function getFirstAvailableTab()
	{
		return sproutEmail()->getFirstAvailableTab();
	}

	/**
	 * Returns the value of the displayDateScheduled general config setting
	 *
	 * @return bool
	 */
	public function getDisplayDateScheduled()
	{
		return sproutEmail()->getConfig('displayDateScheduled', false);
	}
}
