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
	 * @param bool $includeMailersNotYetLoaded
	 *
	 * @return SproutEmailBaseMailer[]
	 */
	public function getMailers($installedOnly = false, $includeMailersNotYetLoaded = false)
	{
		if (is_null($this->mailers) || $includeMailersNotYetLoaded)
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
								// Prioritize built in mailers
								$mailers = $this->mailers;

								if ($this->mailerExists($mailer->getId(), $mailers))
								{
									continue;
								}

								$this->mailers[$mailer->getId()] = $mailer;
							}
						}
					}
				}
			}
		}

		natsort($this->mailers);

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
	 * @param string $name
	 * @param bool   $includeMailersNotYetLoaded
	 *
	 * @return SproutEmailBaseMailer|null
	 */
	public function getMailerByName($name, $includeMailersNotYetLoaded = false)
	{
		if ($includeMailersNotYetLoaded)
		{
			$this->mailers = $this->getMailers(false, true);
		}

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
		$settings = null;

		if (is_null($this->configs))
		{
			$this->configs = craft()->config->get('sproutEmail');
		}

		if (isset($this->configs['apiSettings'][$name]))
		{
			$configs = $this->configs['apiSettings'][$name];
		}

		if (($mailer = $this->getMailerByName($name, true)))
		{
			$settings = new Model($mailer->defineSettings());
		}

		if ($mailer)
		{
			$record           = $this->getMailerRecordByName($name);
			$settingsFromDb   = isset($record->settings) ? $record->settings : array();
			$settingsFromFile = isset($configs) ? $configs : array();

			$settings->setAttributes(array_merge($settingsFromDb, $settingsFromFile));
		}

		return $settings;
	}

	/**
	 * @param $mailer
	 *
	 * @return array|bool
	 */
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
	 * @param $emailId
	 * @param $campaignTypeId
	 *
	 * @return array )
	 * @throws Exception
	 */
	public function getPrepareModal($mailerName, $emailId, $campaignTypeId)
	{
		$mailer = $this->getMailerByName($mailerName);

		if (!$mailer)
		{
			throw new Exception(Craft::t('No mailer with id {id} was found.', array('id' => $mailerName)));
		}

		$campaignEmail = sproutEmail()->campaignEmails->getCampaignEmailById($emailId);
		$campaignType  = sproutEmail()->campaignTypes->getCampaignTypeById($campaignTypeId);
		$response      = new SproutEmail_ResponseModel();

		if ($campaignEmail && $campaignType)
		{
			try
			{
				$response->success = true;
				$response->content = $mailer->getPrepareModalHtml($campaignEmail, $campaignType);

				return $response;
			}
			catch (\Exception $e)
			{
				$response->success = false;
				$response->message = $e->getMessage();

				return $response;
			}
		}
		else
		{
			$name              = $mailer->getTitle();
			$response->success = false;
			$response->message = "<h1>$name</h1><br><p>" . Craft::t('No actions available for this campaign entry.') . "</p>";
		}

		return $response;
	}

	public function includeMailerModalResources()
	{
		craft()->templates->includeCssResource('sproutemail/css/modal.css');

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
	 * @param SproutEmail_CampaignEmailModel $campaignEmail
	 * @param SproutEmail_CampaignTypeModel  $campaignType
	 *
	 * @return array
	 * @throws Exception
	 * @throws \Exception
	 */
	public function sendCampaignEmail(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignTypeModel $campaignType)
	{
		$mailer = $this->getMailerByName($campaignType->mailer);

		if (!$mailer || !$mailer->isInstalled())
		{
			throw new Exception(Craft::t('No mailer with id {id} was found.', array('id' => $campaignType->mailer)));
		}

		try
		{
			return $mailer->sendCampaignEmail($campaignEmail, $campaignType);
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
	 *
	 * @throws Exception
	 */
	public function installMailer($name)
	{
		$mailer = $this->getMailerByName($name, true);

		if (!$mailer)
		{
			throw new Exception(Craft::t('The {name} mailer is not available for installation.', array(
				'name' => $name
			)));
		}

		if ($mailer->isInstalled())
		{
			sproutEmail()->info(Craft::t('The {name} mailer is already installed.', array(
				'name' => $name
			)));
		}
		else
		{
			$this->createMailerRecord($mailer);
		}
	}

	/**
	 * Installs the mailer and its settings if not already installed
	 *
	 * @param $name
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function uninstallMailer($name)
	{
		$mailer = $this->getMailerByName($name, true);

		$builtInMailers = array('copypaste');

		// Do not remove builtin mailers settings
		if (in_array($name, $builtInMailers))
		{
			return false;
		}

		if (!$mailer)
		{
			throw new Exception(Craft::t('The {name} mailer was not found.', array(
				'name' => $name
			)));
		}

		if (!$mailer->isInstalled())
		{
			sproutEmail()->info(Craft::t('The {name} mailer is not installed, no need to uninstall.', array(
				'name' => $name
			)));
		}
		else
		{
			$this->deleteMailerRecord($mailer->getId());
		}
	}

	/**
	 * Creates a new record for a mailer with its name and settings
	 *
	 * @param SproutEmailBaseMailer $mailer
	 *
	 * @return bool
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
			sproutEmail()->error(Craft::t('Unable to install the {name} mailer.{message}', array(
				'name'    => $mailer->getId(),
				'message' => PHP_EOL . $e->getMessage(),
			)));
		}
	}

	/**
	 * Deletes a mailer record by name
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	protected function deleteMailerRecord($name)
	{
		$record = $this->getMailerRecordByName($name);

		if (!$record)
		{
			sproutEmail()->error(Craft::t('No {name} mailer record to delete.', array(
				'name' => $name
			)));

			return false;
		}

		try
		{
			return $record->delete();
		}
		catch (\Exception $e)
		{
			sproutEmail()->error(Craft::t('Unable to delete the {name} mailer record.{message}', array(
				'name'    => $name,
				'message' => PHP_EOL . $e->getMessage(),
			)));
		}
	}

	/*
	 * Check if a Mailer exists
	 *
	 * @param $mailerId key
	 * @param $mailers
	 * @return bool
	 */
	protected function mailerExists($mailerId, $mailers)
	{
		if ($mailers != null)
		{
			$mailerKeys = array_keys($mailers);

			if (in_array($mailerId, $mailerKeys))
			{
				return true;
			}
		}

		return false;
	}

	public function getCheckboxFieldValue($options)
	{
		$value = '*';

		if (isset($options))
		{
			if ($options == '')
			{
				// Uncheck all checkboxes
				$value = 'x';
			}
			else
			{
				$value = $options;
			}
		}

		return $value;
	}

	public function isArraySettingsMatch($array = array(), $options)
	{
		if ($options == '*')
		{
			return true;
		}

		if (is_array($options))
		{
			$intersect = array_intersect($array, $options);

			if (!empty($intersect))
			{
				return true;
			}
		}

		return false;
	}
}
