<?php
namespace Craft;

/**
 * Email provider interface
 * All provider service must implement these methods
 */
interface SproutEmail_EmailProviderInterfaceService
{
	public function getSubscriberList();
	public function exportEmailBlast($emailBlastType, $listIds, $return);
	public function sendEmailBlast($emailBlastType, $listIds);
	public function saveRecipientList(SproutEmail_EmailBlastTypeModel &$emailBlastType, SproutEmail_EmailBlastTypeRecord &$emailBlastTypeRecord);
	public function cleanUpRecipientListOrphans(&$emailBlastTypeRecord);
	public function getSettings();
	public function saveSettings($settings = array());
}
