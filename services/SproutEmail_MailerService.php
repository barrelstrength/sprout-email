<?php
namespace Craft;

class SproutEmail_MailerService extends BaseApplicationComponent
{
	/**
	 * @var SproutEmail_BaseMailer[]
	 */
	protected $mailers;

	public function init()
	{
		$this->mailers = $this->getMailers();
	}

	public function installMailers()
	{
		$mailers = $this->getMailers();

		foreach ($mailers as $mailer)
		{
			$record = $this->getSettingsRecordByMailerName($mailer->getId());

			if (!$record)
			{
				$record = new SproutEmail_MailerSettingsRecord();

				$record->setAttribute('name', $mailer->getId());
				$record->setAttribute('settings', $mailer->getDefaultSettings());
				$record->save();
			}
		}
	}

	/**
	 * Returns all the available email services that sprout email can use
	 *
	 * @return SproutEmailBaseMailer[]
	 */
	public function getMailers()
	{
		if (null === $this->mailers)
		{
			$responses = craft()->plugins->call('defineSproutEmailMailers');

			if ($responses)
			{
				foreach ($responses as $plugin => $mailers)
				{
					if (is_array($mailers) && count($mailers))
					{
						foreach ($mailers as $name => $mailer)
						{
							$this->mailers[$mailer->getId()] = $mailer;
						}
					}
				}
			}
		}

		return $this->mailers;
	}

	public function getMailerByName($name)
	{
		return isset($this->mailers[$name]) ? $this->mailers[$name] : null;
	}

	/**
	 * @param $name
	 *
	 * @return SproutEmail_MailerSettingsRecord
	 */
	public function getSettingsRecordByMailerName($name)
	{
		return SproutEmail_MailerSettingsRecord::model()->findByAttributes(array('name' => $name));
	}

	/**
	 * @param $name
	 *
	 * @return array
	 */
	public function getSettingsByMailerName($name)
	{
		if (($record = $this->getSettingsRecordByMailerName($name)))
		{
			return $record->settings;
		} 
	}
}
