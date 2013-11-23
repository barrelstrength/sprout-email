<?php
namespace Craft;

/**
 * Email provider interface
 * All provider service must implement these methods
 */
interface MasterBlaster_EmailProviderInterfaceService
{
	public function getSubscriberList();
	public function exportCampaign($campaign, $listIds);
	public function sendCampaign($campaign, $listIds);
	public function saveRecipientList(MasterBlaster_CampaignModel &$campaign, MasterBlaster_CampaignRecord &$campaignRecord);
	public function cleanUpRecipientListOrphans(&$campaignRecord);
}
