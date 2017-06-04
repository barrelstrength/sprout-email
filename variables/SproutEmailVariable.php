<?php

namespace Craft;

class SproutEmailVariable
{
	/**
	 * @var ElementCriteriaModel
	 */
	public $entries;

	/**
	 * SproutEmailVariable constructor.
	 */
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
	 * Returns a specific mailer using the mailer handle
	 *
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

	/**
	 * Returns a Campaign Email by id
	 *
	 * @param $emailId
	 *
	 * @return SproutEmail_CampaignEmailModel
	 */
	public function getCampaignEmailById($emailId)
	{
		return sproutEmail()->campaignEmails->getCampaignEmailById($emailId);
	}

	/**
	 * Returns a Campaign Email Share URL and Token
	 *
	 * @param $emailId
	 * @param $campaignTypeId
	 *
	 * @return array|string
	 */
	public function getCampaignEmailShareUrl($emailId, $campaignTypeId)
	{
		return UrlHelper::getActionUrl('sproutEmail/campaignEmails/shareCampaignEmail', array(
			'emailId'        => $emailId,
			'campaignTypeId' => $campaignTypeId
		));
	}

	/**
	 * Returns a Sent Email by id
	 *
	 * @param $emailId
	 *
	 * @return mixed
	 */
	public function getSentEmailById($emailId)
	{
		return sproutEmail()->sentEmails->getSentEmailById($emailId);
	}

	/**
	 * Confirm if a users Craft install can generate Sprout Email example templates
	 *
	 * @return bool
	 */
	public function canCreateExamples()
	{
		return sproutEmail()->canCreateExamples();
	}

	/**
	 * Returns the site templates path
	 *
	 * @return string
	 */
	public function getTemplatePath()
	{
		return craft()->path->getSiteTemplatesPath();
	}

	/**
	 * Checks if Sprout Email examples are already installed in the default example location
	 *
	 * @return bool
	 */
	public function hasExamples()
	{
		return sproutEmail()->hasExamples();
	}

	/**
	 * Returns the first Sprout Email sidebar tab in the CP that should be displayed
	 *
	 * @return string
	 */
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
