<?php
namespace Craft;

class SproutEmail_CopyPasteMailer extends SproutEmailBaseMailer implements SproutEmailCampaignEmailSenderInterface
{
	protected $service;

	/**
	 * @return SproutEmailCopyPasteService
	 */
	public function getService()
	{
		if (null === $this->service)
		{
			$this->service = Craft::app()->getComponent('sproutEmail_copyPaste');

			$this->service->setSettings($this->getSettings());
		}

		return $this->service;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'copypaste';
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return 'Copy/Paste';
	}

	/**
	 * @return string
	 */
	public function getDescription()
	{
		return "Copy and paste your email campaigns to better (or worse) places.";
	}

	/**
	 * @return string
	 */
	public function getActionForPrepareModal()
	{
		return 'sproutEmail/campaignEmails/sendCampaignEmail';
	}

	/**
	 * @param SproutEmail_CampaignEmailModel $campaignEmail
	 * @param SproutEmail_CampaignTypeModel  $campaignType
	 *
	 * @return mixed
	 */
	public function getPrepareModalHtml(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignTypeModel $campaignType)
	{
		return '';
	}

	/**
	 * Gives mailers the ability to include their own modal resources and register their dynamic action handlers
	 */
	public function includeModalResources()
	{
		craft()->templates->includeJsResource('sproutemail/js/mailers/copypaste.js');
	}

	/**
	 * @param SproutEmail_CampaignEmailModel $campaignEmail
	 * @param SproutEmail_CampaignTypeModel  $campaignType
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function sendCampaignEmail(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignTypeModel $campaignType)
	{
		$this->includeModalResources();

		try
		{
			return $this->getService()->sendCampaignEmail($campaignEmail, $campaignType);
		}
		catch (\Exception $e)
		{
			throw $e;
		}
	}
}
