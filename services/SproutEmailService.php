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
}
