<?php
namespace Craft;

class SproutEmail_MailGunMailer extends SproutEmailBaseMailer
{
	public function getName()
	{
		return 'mailgun';
	}

	public function getTitle()
	{
		return 'Mail Gun Service';
	}

	public function getDescription()
	{
		return 'The Mail Gun email service.';
	}

	public function getDefaultSettings()
	{
		return array(
			'apiKey' => '',
			'domain' => '',
		);
	}

	public function getSettingsHtml(array $context = array())
	{
		$context['settings'] = $this->getSettings();

		$html = craft()->templates->render('sproutemail/_mailers/settings.mailgun.html', $context);

		return TemplateHelper::getRaw($html);
	}

	public function canSendNotifications()
	{
		return true;
	}

	public function sendNotification($notification, BaseModel $model)
	{
		Craft::dump($notification);
		echo '<hr>';
		Craft::dump($_POST);
		echo '<hr>';
		Craft::dump($model->getAttributes());
	}

	public function getSubscriberList()
	{
		$service = craft()->getComponent('sproutEmail_mailerMailgun');

		if ($service)
		{
			$service->setSettings($this->getSettings());

			return $service->getSubscriberList();
		}

		throw new Exception('No service available to fetch subscriber lists.');
	}

	public function getCampaignList()
	{
		$service = craft()->getComponent('sproutEmail_mailerMailgun');

		if ($service)
		{
			$service->setSettings($this->getSettings());

			return $service->getCampaignList();
		}

		throw new Exception('No service available to fetch campaign lists.');
	}

	public function prepareRecipientList(SproutEmail_EntryModel $entry, SproutEmail_CampaignModel $campaign)
	{
		$model = new SproutEmail_EntryRecipientListModel();

		$model->setAttribute('entryId', $entry->id);
		$model->setAttribute('mailer', $this->getId());
		$model->setAttribute('list', array_shift(craft()->request->getPost('mailgun.list', array())));
		$model->setAttribute('type', $campaign->type);

		return $model;
	}
}
