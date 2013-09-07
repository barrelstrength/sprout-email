<?php
namespace Craft;

/**
 * Base email provider service
 */
class MasterBlaster_EmailProviderService extends BaseApplicationComponent
{
	/**
	 * Returns supported email providers based on which libraries
	 * are installed in /masterblaster/libraries
	 * @return multitype:string NULL
	 */
	public function getEmailProviders()
	{
		$plugins_path = rtrim(craft()->path->getPluginsPath(), '\\/');
		$files = scandir($plugins_path . '/masterblaster/libraries');
		$select_options = array('masterblaster' => 'Master Blaster');
			
		// set <select> values and text to be the same
		foreach($files as $file)
		{
			if ($file !== '.' && $file !== '..')
			{
				$select_options[$file] = ucwords(str_replace('_', ' ', $file));
			}
		}
	
		return $select_options;
	}
	
	/**
	 * Base function for exporting section based emails
	 * 
	 * @param int $entryId
	 * @param int $campaignId
	 */
	public function exportCampaign($entryId, $campaignId)
	{
		if( ! $campaign = craft()->masterBlaster->getSectionBasedCampaignByEntryAndCampaignId($entryId, $campaignId))
		{
			die('Campaign not found');
		}
		if( ! $recipientLists = craft()->masterBlaster->getCampaignRecipientLists($campaign['id']))
		{
			die('Recipient lists not found');
		}

		$listIds = array();
		$listProviders = array();
		foreach($recipientLists as $list)
		{
			$listProviders[] = $list->emailProvider;
			$listIds[$list->emailProvider][] = $list['emailProviderRecipientListId'];
		}
		
		foreach($listProviders as $provider)
		{
			$provider_service = 'masterBlaster_' . $provider;
			craft()->{$provider_service}->exportCampaign($campaign, $listIds[$provider]);
		}
		die();
	}
}
