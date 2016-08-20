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

	public function getTitle()
	{
		return 'Copy/Paste';
	}

	public function getName()
	{
		return 'copypaste';
	}

	public function getDescription()
	{
		return "Copy and paste your email campaigns to better (or worse) places.";
	}

	public function getPrepareModalHtml(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignTypeModel $campaignType)
	{
		craft()->templates->includeJsResource('sproutemail/js/mailers/copypaste.js');

		return craft()->templates->render('sproutemail/_modal', array(
			'entry'    => $campaignEmail,
			'campaign' => $campaignType
		));
	}

	public function getActionForPrepareModal()
	{
		return 'sproutEmail/campaignEmails/export';
	}

	public function getPreviewModalHtml(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignTypeModel $campaignType)
	{
		return $this->getService()->previewCampaignEmail($campaignEmail, $campaignType);
	}

	public function getActionForPreview()
	{
		return 'sproutEmail/campaignEmails/preview';
	}

	public function includeModalResources()
	{
		craft()->templates->includeJsResource('sproutemail/js/mailers/copypaste.js');
	}

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

	public function previewCampaignEmail(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignTypeModel $campaignType)
	{
		try
		{
			return $this->getService()->previewCampaignEmail($campaignEmail, $campaignType);
		}
		catch (\Exception $e)
		{
			throw $e;
		}
	}
}
