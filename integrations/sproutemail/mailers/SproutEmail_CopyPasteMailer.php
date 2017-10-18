<?php

namespace Craft;

class SproutEmail_CopyPasteMailer extends SproutEmailBaseMailer implements SproutEmailCampaignEmailSenderInterface
{
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
		return Craft::t('Copy and paste your email campaigns to better (or worse) places.');
	}

	/**
	 * @return bool
	 */
	public function hasLists()
	{
		return false;
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
			$variables = array(
				'email'        => $campaignEmail,
				'campaignType' => $campaignType
			);

			$html = sproutEmail()->renderSiteTemplateIfExists($campaignType->template, $variables);
			$text = sproutEmail()->renderSiteTemplateIfExists($campaignType->template . '.txt', $variables);

			$response          = new SproutEmail_ResponseModel();
			$response->success = true;
			$response->content = craft()->templates->render('sproutemail/_integrations/mailers/copypaste/scheduleCampaignEmail',
				array(
				'email' => $campaignEmail,
				'html'  => trim($html),
				'text'  => trim($text),
			));

			return $response;
		}
		catch (\Exception $e)
		{
			throw $e;
		}
	}
}
