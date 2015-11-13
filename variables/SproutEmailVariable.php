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
		$this->entries = craft()->elements->getCriteria('SproutEmail_Entry');
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
	 * @return SproutEmailBaseMailer[]
	 */
	public function getCampaignMailers()
	{
		$mailers = $this->getMailers();

		unset($mailers['defaultmailer']);

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
		return sproutEmail()->notifications->getAvailableEvents();
	}

	public function getSelectedOptions($event, $campaignId)
	{
		if (craft()->request->getRequestType() == 'POST')
		{
			return $event->prepareOptions();
		}

		$notification = sproutEmail()->notifications->getNotification(
			array(
				'eventId'    => $event->getId(),
				'campaignId' => $campaignId
			)
		);

		if ($notification)
		{
			return $event->prepareValue($notification->options);
		}
	}

	/**
	 * Returns a notification entry model if found by campaing id
	 *
	 * @param int $id
	 *
	 * @return SproutEmail_EntryModel|null
	 */
	public function getNotificationEntryByCampaignId($id)
	{
		return sproutEmail()->notifications->getNotificationEntryByCampaignId($id);
	}

	/**
	 * Return Campaign model by passing entry id
	 * @param $id
	 * @return bool|SproutEmail_CampaignModel
	 */
	public function getCampaignByEntryId($id)
	{
		return sproutEmail()->campaigns->getCampaignByEntryId($id);
	}

	/**
	 * @param string $type
	 *
	 * @return mixed Campaign model
	 */
	public function getCampaigns($type = null)
	{
		return sproutEmail()->campaigns->getCampaigns($type);
	}

	/**
	 * Get a Campaign by id *
	 *
	 * @param int $campaignId
	 *
	 * @return object campaign record
	 */
	public function getCampaignById($campaignId)
	{
		return sproutEmail()->campaigns->getCampaignById($campaignId);
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

	/**
	 * Get campaign list for specified provider
	 *
	 * @param string $mailer
	 *
	 * @return array
	 */
	public function getCampaignList($mailer)
	{
		$mailer = sproutEmail()->mailers->getMailerByName(strtolower($mailer));

		return $mailer->getCampaignList();
	}

	public function getGeneralSettingsTemplate($emailProvider = null)
	{
		$customTemplate       = 'sproutemail/_providers/'.$emailProvider.'/generalCampaignSettings';
		$customTemplateExists = craft()->templates->doesTemplateExist($customTemplate);

		// if there is a custom set of general settings for this provider, return those; if not, return the default
		if ($customTemplateExists)
		{
			return true;
		}

		return false;
	}

	public function getRecipientLists($mailer)
	{
		return sproutEmail()->mailers->getRecipientLists($mailer);
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
}
