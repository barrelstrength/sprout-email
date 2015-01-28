<?php
namespace Craft;

/**
 * Mailer plugin manager service
 *
 * Class SproutEmail_MailerService
 *
 * @package Craft
 */
class SproutEmail_MailerService extends BaseApplicationComponent
{
	/**
	 * Sprout Email file configs
	 *
	 * @var array
	 */
	protected $configs;

	/**
	 * @var SproutEmailBaseMailer[]
	 */
	protected $mailers;

	/**
	 * Loads all mailers for later use
	 */
	public function init()
	{
		$this->mailers = $this->getMailers();
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
	 * @param bool $installedOnly
	 *
	 * @return SproutEmailBaseMailer[]
	 */
	public function getMailers($installedOnly=false)
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
							if (!$installedOnly || $mailer->isInstalled())
							{
								$this->mailers[$mailer->getId()] = $mailer;
							}
						}
					}
				}
			}
		}

		return $this->mailers;
	}

	/**
	 * Return a list of installed/registered mailers ready for use
	 *
	 * @return SproutEmailBaseMailer[]
	 */
	public function getInstalledMailers()
	{
		return $this->getMailers(true);
	}

	/**
	 * @param $name
	 *
	 * @return SproutEmailBaseMailer|null
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
		if (null === $this->configs)
		{
			$this->configs = craft()->config->get('sproutEmail');
		}

		if (isset($this->configs['apiSettings'][$name]))
		{
			$configs = $this->configs['apiSettings'][$name];
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
	 * Returns a link to the control panel section for the mailer passed in
	 *
	 * @param SproutEmailBaseMailer $mailer
	 *
	 * @return string|\Twig_Markup
	 */
	public function getMailerCpSectionLink(SproutEmailBaseMailer $mailer)
	{
		$vars = array(
			'name'        => $mailer->getId(),
			'title'       => $mailer->getTitle(),
			'sproutemail' => UrlHelper::getCpUrl('sproutemail'),
		);

		$template = '<a href="{sproutemail}/{name}" title="{title}">{title}</a>';

		try
		{
			$link = craft()->templates->renderObjectTemplate($template, $vars);

			return TemplateHelper::getRaw($link);
		}
		catch (\Exception $e)
		{
			sproutEmail()->error('Unable to create Control Panel Section link for {name}', $vars);

			return $mailer->getTitle();
		}
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

	/**
	 * @param $mailerName
	 * @param $entryId
	 * @param $campaignId
	 *
	 * @return array(content => '', actions => array())
	 * @throws Exception
	 */
	public function getPrepareModal($mailerName, $entryId, $campaignId)
	{
		$mailer = $this->getMailerByName($mailerName);

		if (!$mailer)
		{
			throw new Exception(Craft::t('No mailer with id {id} was found.', array('id' => $mailerName)));
		}

		$entry    = sproutEmail()->entries->getEntryById($entryId);
		$campaign = sproutEmail()->campaigns->getCampaignById($campaignId);
		$modal    = new SproutEmail_ResponseModel();

		if ($entry && $campaign)
		{
			$modal->content = $mailer->getPrepareModalHtml($entry, $campaign);

			return $modal;
		}
		else
		{
			$name = $mailer->getTitle();

			$modal->content = "<h1>$name</h1><br><p>No actions available for this campaign entry.</p>";
		}

		return $modal;
	}

	/**
	 * @param $mailerName
	 * @param $entryId
	 * @param $campaignId
	 *
	 * @return array(content => '', actions => array())
	 * @throws Exception
	 */
	public function getPreviewModal($mailerName, $entryId, $campaignId)
	{
		$mailer = $this->getMailerByName($mailerName);

		if (!$mailer)
		{
			throw new Exception(Craft::t('No mailer with id {id} was found.', array('id' => $campaign->mailer)));
		}

		$entry    = sproutEmail()->entries->getEntryById($entryId);
		$campaign = sproutEmail()->campaigns->getCampaignById($campaignId);
		$modal    = new SproutEmail_ResponseModel();

		if ($entry && $campaign)
		{
			$modal->content = $mailer->getPreviewModalHtml($entry, $campaign);

			return $modal;
		}
		else
		{
			$name = $mailer->getTitle();

			$modal->content = "<h1>$name</h1><br><p>No actions available for this campaign entry.</p>";
		}

		return $modal;
	}

	public function includeMailerModalResources()
	{
		$mailers = $this->getInstalledMailers();

		if (count($mailers))
		{
			foreach ($mailers as $mailer)
			{
				$mailer->includeModalResources();
			}
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
				$record = SproutEmail_EntryRecipientListRecord::model()->findByAttributes(
					array(
						'entryId' => $entry->id,
						'list'    => $list->list
					)
				);
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

	/**
	 * @param SproutEmail_EntryModel    $entry
	 * @param SproutEmail_CampaignModel $campaign
	 *
	 * @return array
	 *
	 * @throws Exception
	 * @throws \Exception
	 */
	public function exportEntry(SproutEmail_EntryModel $entry, SproutEmail_CampaignModel $campaign)
	{
		$mailer = $this->getMailerByName($campaign->mailer);

		if (!$mailer || !$mailer->isInstalled())
		{
			throw new Exception(Craft::t('No mailer with id {id} was found.', array('id' => $campaign->mailer)));
		}

		try
		{
			return $mailer->exportEntry($entry, $campaign);
		}
		catch (\Exception $e)
		{
			throw $e;
		}
	}

	/**
	 * @param SproutEmail_EntryModel    $entry
	 * @param SproutEmail_CampaignModel $campaign
	 *
	 * @return array
	 *
	 * @throws Exception
	 * @throws \Exception
	 */
	public function previewEntry(SproutEmail_EntryModel $entry, SproutEmail_CampaignModel $campaign)
	{
		$mailer = $this->getMailerByName($campaign->mailer);

		if (!$mailer)
		{
			throw new Exception(Craft::t('No mailer with id {id} was found.', array('id' => $campaign->mailer)));
		}

		try
		{
			return $mailer->previewEntry($entry, $campaign);
		}
		catch (\Exception $e)
		{
			throw $e;
		}
	}

	/**
	 * Installs all available mailers and their settings if not already installed
	 */
	public function installMailers()
	{
		if ($this->mailers && count($this->mailers))
		{
			foreach ($this->mailers as $mailer)
			{
				$this->installMailer($mailer->getId());
			}
		}
	}

	/**
	 * Installs the mailer and its settings if not already installed
	 *
	 * @param $name
	 */
	public function installMailer($name)
	{
		$vars   = array('name' => $name);
		$mailer = $this->getMailerByName($name);

		if (!$mailer)
		{
			sproutEmail()->error('The {name} mailer is the available for installation.', $vars);
		}

		if ($mailer->isInstalled())
		{
			sproutEmail()->info('The {name} mailer is already installed.', $vars);
		}

		$this->createMailerRecord($mailer);
	}

	/**
	 * Installs the mailer and its settings if not already installed
	 *
	 * @param $name
	 */
	public function uninstallMailer($name)
	{
		$vars   = array('name' => $name);
		$mailer = $this->getMailerByName($name);

		if (!$mailer)
		{
			sproutEmail()->error('The {name} mailer was not found.', $vars);
		}

		if (!$mailer->isInstalled())
		{
			sproutEmail()->info('The {name} mailer is installed, no need to uninstall.', $vars);
		}

		$this->deleteMailerRecord($mailer->getId());
	}

	/**
	 * Creates a new record for a mailer with its name and settings
	 *
	 * @param SproutEmailBaseMailer $mailer
	 */
	protected function createMailerRecord(SproutEmailBaseMailer $mailer)
	{
		$record = new SproutEmail_MailerRecord();

		$record->setAttribute('name', $mailer->getId());
		$record->setAttribute('settings', $mailer->getSettings());

		try
		{
			return $record->save();
		}
		catch (\Exception $e)
		{
			$vars = array(
				'name'    => $mailer->getId(),
				'message' => PHP_EOL.$e->getMessage(),
			);

			sproutEmail()->error('Unable to install the {name} mailer.{message}', $vars);
		}
	}

	/**
	 * Deletes a mailer record by name
	 *
	 * @param string $name
	 */
	protected function deleteMailerRecord($name)
	{
		$record = $this->getMailerRecordByName($name);

		if (!$record)
		{
			sproutEmail()->error('No {name} mailer record to delete.', array('name' => $name));

			return false;
		}

		try
		{
			return $record->delete();
		}
		catch (\Exception $e)
		{
			$vars = array(
				'name'    => $name,
				'message' => PHP_EOL.$e->getMessage(),
			);

			sproutEmail()->error('Unable to delete the {name} mailer record.{message}', $vars);
		}
	}
}
