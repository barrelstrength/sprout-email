<?php
namespace Craft;

class SproutEmail_CopyPasteMailer extends SproutEmailBaseMailer
{
	public function getName()
	{
		return 'copypaste';
	}

	public function getTitle()
	{
		return 'Copy Paste Service';
	}

	public function getDescription()
	{
		return 'Outputs the notification to the screen.';
	}

	public function getDefaultSettings()
	{
		return array(
			'enabled' => true,
		);
	}

	public function getSettingsHtml(array $context = array())
	{
		$context['settings'] = $this->getSettings();

		$html = craft()->templates->render('sproutemail/_providers/settings.copypaste.html', $context);

		return TemplateHelper::getRaw($html);
	}

	public function canSendNotifications()
	{
		return false;
	}

	public function getSubscriberList()
	{
		$service = craft()->getComponent('sproutEmail_copyPasteMailer');

		if ($service)
		{
			$service->setSettings($this->getSettings());

			return $service->getSubscriberList();
		}

		throw new Exception('No service available to fetch subscriber lists.');
	}

	public function exportEntry($campaign, $listIds = array())
	{
		$service = craft()->getComponent('sproutEmail_copyPasteMailer');

		if ($service)
		{
			$service->setSettings($this->getSettings());

			$service->exportEntry($campaign, $listIds);
		}
		else
		{
			throw new Exception('No service available to export entries.');
		}
	}
}
