<?php
namespace Craft;

class SproutEmail_MailerService extends BaseApplicationComponent
{
	/**
	 * Sprout Email file configs
	 *
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

		if ($mailers && count($mailers))
		{
			foreach ($mailers as $mailer)
			{
				if (!$this->isInstalled($mailer->getId()))
				{
					$record = new SproutEmail_MailerRecord();

					$record->setAttribute('name', $mailer->getId());
					$record->setAttribute('settings', $mailer->getSettings());
					$record->save();
				}
			}
		}
	}

	/**
	 * Returns whether or not the mailer is installed
	 *
	 * @param $name
	 *
	 * @return bool
	 */
	public function isInstalled($name)
	{
		$record = $this->getMailerRecordByName($name);

		return ($record && $record->name == $name);
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
	 * @return SproutEmail_MailerRecord
	 */
	public function getMailerRecordByName($name)
	{
		return SproutEmail_MailerRecord::model()->findByAttributes(array('name' => $name));
	}

	/**
	 * @param $name
	 *
	 * @return Model|null
	 */
	public function getSettingsByMailerName($name)
	{
		if (null === $this->fileConfigs)
		{
			$this->fileConfigs = craft()->config->get('sproutEmail');
		}

		if (isset($this->fileConfigs['apiSettings'][$name]))
		{
			$configs = $this->fileConfigs['apiSettings'][$name];
		}

		if (($mailer = $this->getMailerByName($name)))
		{
			$settings = new Model($mailer->defineSettings());
		}

		if ($mailer && $settings && ($record = $this->getMailerRecordByName($name)))
		{
			$settingsFromDb   = $record->settings ? $record->settings : array();
			$settingsFromFile = isset($configs) ? $configs : array();

			$settings->setAttributes(array_merge($settingsFromDb, $settingsFromFile));

			return $settings;
		}
	}

	/**
	 * @param SproutEmail_CampaignModel $campaign
	 * @param SproutEmail_EntryModel    $entry
	 *
	 * @throws Exception
	 * @throws \Exception
	 * @return bool
	 */
	public function saveRecipientLists(SproutEmail_CampaignModel $campaign, SproutEmail_EntryModel $entry)
	{
		$mailer = $this->getMailerByName($campaign->mailer);

		if (!$mailer)
		{
			throw new Exception(Craft::t('The {m} mailer is not supported.', array('m' => $campaign->mailer)));
		}

		sproutEmail()->entries->deleteRecipientListsByEntryId($entry->id);

		$lists = $mailer->prepareRecipientLists($entry, $campaign);

		if ($lists && is_array($lists) && count($lists))
		{
			foreach ($lists as $list)
			{
				$record = SproutEmail_EntryRecipientListRecord::model()->findByAttributes(array('entryId' => $entry->id, 'list' => $list->list));
				$record = $record ? $record : new SproutEmail_EntryRecipientListRecord();

				$record->entryId = $list->entryId;
				$record->mailer  = $list->mailer;
				$record->list    = $list->list;
				$record->type    = $list->type;

				try
				{
					$record->save();
				}
				catch (\Exception $e)
				{
					throw $e;
				}
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

	public function exportEntry(SproutEmail_EntryModel $entry, SproutEmail_CampaignModel $campaign)
	{
		$mailer = $this->getMailerByName($campaign->mailer);

		if (!$mailer)
		{
			throw new Exception(Craft::t('No service provider with id {id} was found.', array('id' => $campaign->mailer)));
		}

		try
		{
			$mailer->exportEntry($entry, $campaign);
		}
		catch (\Exception $e)
		{
			throw $e;
		}
	}
}
