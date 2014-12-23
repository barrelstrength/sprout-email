<?php
namespace Craft;

class SproutEmail_MailerService extends BaseApplicationComponent
{
	/**
	 * Sprout Email file configs
	 * @var array
	 */
	protected $fileConfigs;

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

	/**
	 * @param $name
	 *
	 * @return SproutEmail_BaseMailer|null
	 */
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
		if (null === $this->fileConfigs)
		{
			$this->fileConfigs = craft()->config->get('sproutEmail');
		}

		if (isset($this->fileConfigs['apiSettings'][$name]))
		{
			return $this->fileConfigs['apiSettings'][$name];
		}

		if (($record = $this->getSettingsRecordByMailerName($name)))
		{
			return $record->settings;
		}
	}

	/**
	 * @param SproutEmail_CampaignModel $campaign
	 * @param SproutEmail_EntryModel    $entry
	 *
	 * @throws Exception
	 * @return bool
	 */
	public function saveRecipientList(SproutEmail_CampaignModel $campaign, SproutEmail_EntryModel $entry)
	{
		$mailer = $this->getMailerByName($campaign->mailer);

		if (!$mailer)
		{
			throw new Exception(Craft::t('The {m} mailer is not supported.', array('m' => $campaign->mailer)));
		}

		$model = $mailer->prepareRecipientList($entry, $campaign);

		if ($model)
		{
			$record = SproutEmail_EntryRecipientListRecord::model()->findByAttributes(array('entryId' => $entry->id));
			$record = $record ? $record : new SproutEmail_EntryRecipientListRecord();

			$record->entryId = $model->entryId;
			$record->mailer  = $model->mailer;
			$record->list    = $model->list;
			$record->type    = $model->type;

			try
			{
				$record->save();

				return true;
			}
			catch(\Exception $e)
			{
				throw $e;
			}
		}

		return true;
	}

	public function getRecipientLists($mailer)
	{
		$mailer = $this->getMailerByName($mailer);

		if ($mailer)
		{
			return $mailer->getRecipientLists();
		}

		return false;
	}
}
