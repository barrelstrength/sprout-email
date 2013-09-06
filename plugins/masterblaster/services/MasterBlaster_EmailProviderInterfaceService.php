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
}
