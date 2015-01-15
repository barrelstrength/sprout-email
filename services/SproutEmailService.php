<?php
namespace Craft;

/**
 * Class SproutEmailService
 *
 * @package Craft
 *
 * @property SproutEmail_MailerService           $mailers
 * @property SproutEmail_EntriesService          $entries
 * @property SproutEmail_CampaignsService        $campaigns
 * @property SproutEmail_NotificationsService    $notifications
 */
class SproutEmailService extends BaseApplicationComponent
{
	public $mailers;
	public $entries;
	public $campaigns;
	public $notifications;

	public function init()
	{
		parent::init();

		$this->mailers       = Craft::app()->getComponent('sproutEmail_mailer');
		$this->entries       = Craft::app()->getComponent('sproutEmail_entries');
		$this->campaigns     = Craft::app()->getComponent('sproutEmail_campaigns');
		$this->notifications = Craft::app()->getComponent('sproutEmail_notifications');
	}

	public function exportEntry($entryId, $campaignId, $return = false)
	{
		// Define our variables
		$listIds = array ();
		$listProviders = array ();

		// Get our campaign info
		if ( ! $campaign = craft()->sproutEmail_campaign->getCampaignById($campaignId) )
		{
			SproutEmailPlugin::log("Campaign not found");

			if ( $return )
			{
				return false;
			}

			// @TODO - update use of die
			die( 'Campaign not found' );
		}

		// Get our recipient list info
		if($campaign["emailProvider"] != 'CopyPaste')
		{
			if ( ! $recipientLists = craft()->sproutEmail_campaign->getCampaignRecipientLists( $campaign ['id'] ) )
			{
				SproutEmailPlugin::log("Recipient lists not found");

				if ( $return )
				{
					return false;
				}

				// @TODO - update use of die
				die( 'Recipient lists not found' );
			}
		}
		else
		{
			SproutEmailPlugin::log("Exporting Copy/Paste Email");

			// We can't check for a recipients list on CopyPaste since one doesn't exist
			craft()->sproutEmail_copyPaste->exportEntry( $campaign );

			// @TODO - update use of die
			die();
		}

		// Check to see if we have entry level settings and update
		// before shuffling off to the individual service connectors.

		$entry = craft()->entries->getEntryById($entryId);
		$entryFields = $entry->getFieldLayout()->getFields();

		// Assume we have no override, and update overrideHandle if we do
		$overrideHandle = "";

		foreach ($entryFields as $field)
		{
			// If we have an Email Campaign Field, grab the handle of the first one that matches
			if ($field->getField()->type == 'SproutEmail_EmailCampaign')
			{
				SproutEmailPlugin::log('We have a Campaign Override field');
				SproutEmailPlugin::log('Override Field Handle: ' . $field->getField()->handle);

				$overrideHandle = $field->getField()->handle;
				continue;
			}
		}

		// If the entry has an override handle assigned to it
		if ($overrideHandle != "")
		{
			// Grab our Email Campaign override settings
			$entryOverrideSettings = $entry->{$overrideHandle};
			$entryOverrideSettings = json_decode($entryOverrideSettings,TRUE);

			SproutEmailPlugin::log('Our override settings: ' . $entry->{$overrideHandle});

			// Merge the entry level settings with our campaign
			$emailProviderRecipientListId = '';

			if( isset($entryOverrideSettings) )
			{
				foreach($entryOverrideSettings as $key => $value)
				{
					// Override our campaign settings
					$campaign[$key] = $value;

					if($key == 'emailProviderRecipientListId')
					{
						// Make sure our $emailProviderRecipientListId is an array
						// @TODO - clarify what this value is for.  Is it only for Mailgun?
						$emailProviderRecipientListId = (array) $value;
						$campaign[$key] = $emailProviderRecipientListId;
					}

					SproutEmailPlugin::log('Entry override ' . $key . ': ' . $value);

				}
			}

			// @TODO - need to revisit this behavior big time!
			if($emailProviderRecipientListId != '')
			{
				foreach ($emailProviderRecipientListId as $key => $value)
				{
					$recipientLists[0]->emailProviderRecipientListId = $entryOverrideSettings['emailProviderRecipientListId']['list'];
					$recipientLists[0]->emailProvider = $campaign['emailProvider'];
					$recipientLists[0]->type = null;

					// $recipientListOverrides[$key] = $recipientListOverride;
				}
			}
		}

		// Create the recipient list variables
		foreach ( $recipientLists as $list )
		{
			$listProviders [] = $list->emailProvider;
			$listIds [$list->emailProvider] [] = $list ['emailProviderRecipientListId'];
		}

		foreach ( $listProviders as $provider )
		{
			$provider_service = 'sproutEmail_' . lcfirst( $provider );
			craft()->{$provider_service}->exportEntry( $campaign, $listIds [$provider], $return );
		}

		if ( $return )
		{
			return true;
		}

		// @TODO - update use of die
		die();
	}

	public function renderSiteTemplateIfExists($template, array $variables = array())
	{
		$path     = craft()->path->getTemplatesPath();
		$rendered = null;

		craft()->path->setTemplatesPath(craft()->path->getSiteTemplatesPath());

		if (craft()->templates->doesTemplateExist($template))
		{
			try
			{
				$rendered = craft()->templates->render($template, $variables);
			}
			catch (\Exception $e)
			{
			}
		}

		craft()->path->setTemplatesPath($path);

		return $rendered;
	}
}
