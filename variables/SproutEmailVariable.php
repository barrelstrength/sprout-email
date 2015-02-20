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

	public function getMailers()
	{
		return sproutEmail()->mailers->getMailers();
	}

	/**
	 * @param string $mailer
	 *
	 * @return SproutEmail_BaseMailer|null
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
		$record = SproutEmail_EntryRecord::model()->findByAttributes(array('campaignId' => $id));

		if ($record)
		{
			return SproutEmail_EntryModel::populateModel($record);
		}
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

	/**
	 * Get notification event for specified id
	 *
	 * @param int $id
	 *
	 * @return obj
	 */
	public function getNotificationEventById($id)
	{
		return sproutEmail()->notifications->getNotificationEventById($id);
	}

	public function isSubscribed($userId = null, $elementId = null)
	{
		if (!$userId or !$elementId)
		{
			return false;
		}

		$query = craft()->db->createCommand()->select('userId, elementId')->from('sproutemail_subscriptions')->where(
			array(
				'AND',
				'userId = :userId',
				'elementId = :elementId'
			), array(
				':userId'    => $userId,
				':elementId' => $elementId
			)
		)->queryRow();

		return (is_array($query)) ? true : false;
	}

	public function getSubscriptionIds($userId = null, $elementType = 'Entry', $criteria = array())
	{
		$userId = craft()->userSession->id;

		if (!$userId)
		{
			return false;
		}

		// @TODO - join the sproutemail_subscriptions and elements table to make sure we're only
		// getting back the IDs of the Elements that match our type.

		$results = craft()->db->createCommand()->select('elementId')->from('sproutemail_subscriptions')->where(
			'userId = :userId', array(
				':userId' => $userId
			)
		)->queryAll();

		$ids = "";

		foreach ($results as $key => $value)
		{
			if ($ids == "")
			{
				$ids = $value ['elementId'];
			}
			else
			{
				$ids .= ",".$value ['elementId'];
			}
		}

		return $ids;
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

	/**
	 * Provider specific functions (since there is no support for multiple variable files)
	 */
	public function getSendGridSenderAddresses()
	{
		if (!$senderAddresses = craft()->sproutEmail_sendGrid->getSenderAddresses())
		{
			$senderAddresses[''] = 'Please create a sender address (from name) in your SendGrid account.';
		}

		return $senderAddresses;
	}

	public function getRecipientLists($mailer)
	{
		return sproutEmail()->mailers->getRecipientLists($mailer);
	}
}
