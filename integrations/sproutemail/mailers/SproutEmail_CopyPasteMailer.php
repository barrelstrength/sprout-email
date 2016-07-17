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

	public function getPrepareModalHtml(SproutEmail_CampaignEmailModel $entry, SproutEmail_CampaignModel $campaign)
	{
		craft()->templates->includeJsResource('sproutemail/js/mailers/copypaste.js');

		return craft()->templates->render('sproutemail/_modal', array(
			'entry'    => $entry,
			'campaign' => $campaign
		));
	}

	public function getActionForPrepareModal()
	{
		return 'sproutEmail/campaignEmails/export';
	}

	public function getPreviewModalHtml(SproutEmail_CampaignEmailModel $entry, SproutEmail_CampaignModel $campaign)
	{
		return $this->getService()->previewEntry($entry, $campaign);
	}

	public function getActionForPreview()
	{
		return 'sproutEmail/campaignEmails/preview';
	}

	public function includeModalResources()
	{
		craft()->templates->includeJsResource('sproutemail/js/mailers/copypaste.js');
	}

	public function exportEntry(SproutEmail_CampaignEmailModel $entry, SproutEmail_CampaignModel $campaign)
	{
		$this->includeModalResources();
		try
		{
			return $this->getService()->exportEntry($entry, $campaign);
		}
		catch (\Exception $e)
		{
			throw $e;
		}
	}

	public function previewEntry(SproutEmail_CampaignEmailModel $entry, SproutEmail_CampaignModel $campaign)
	{
		try
		{
			return $this->getService()->previewEntry($entry, $campaign);
		}
		catch (\Exception $e)
		{
			throw $e;
		}
	}
}
