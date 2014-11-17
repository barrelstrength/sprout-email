<?php
namespace Craft;

/**
 * Email provider interface
 * All provider service must implement these methods
 */
interface SproutEmail_EmailProviderInterfaceService
{
	public function getSubscriberList();
	public function exportEntry($campaign, $listIds, $return);
	public function sendEntry($campaign, $listIds);
	public function saveRecipientList(SproutEmail_CampaignModel &$campaign, SproutEmail_CampaignRecord &$campaignRecord);
	public function cleanUpRecipientListOrphans(&$campaignRecord);
	public function getSettings();
	public function saveSettings($settings = array());
}
