<?php
namespace Craft;

class SproutEmail_CopyPasteMailer extends SproutEmailBaseMailer
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

	public function getPrepareModalHtml(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignTypeModel $campaign)
	{
		craft()->templates->includeJsResource('sproutemail/js/mailers/copypaste.js');

		return craft()->templates->render('sproutemail/_modal', array(
			'entry'    => $campaignEmail,
			'campaign' => $campaign
		));
	}

	public function getActionForPrepareModal()
	{
		return 'sproutEmail/campaignEmails/export';
	}

	public function getPreviewModalHtml(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignTypeModel $campaign)
	{
		return $this->getService()->previewCampaignEmail($campaignEmail, $campaign);
	}

	public function getActionForPreview()
	{
		return 'sproutEmail/campaignEmails/preview';
	}

	public function includeModalResources()
	{
		craft()->templates->includeJsResource('sproutemail/js/mailers/copypaste.js');
	}

	public function exportEmail(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignTypeModel $campaign)
	{
		$this->includeModalResources();
		try
		{
			return $this->getService()->exportEmail($campaignEmail, $campaign);
		}
		catch (\Exception $e)
		{
			throw $e;
		}
	}

	public function previewCampaignEmail(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignTypeModel $campaign)
	{
		try
		{
			return $this->getService()->previewCampaignEmail($campaignEmail, $campaign);
		}
		catch (\Exception $e)
		{
			throw $e;
		}
	}
}
