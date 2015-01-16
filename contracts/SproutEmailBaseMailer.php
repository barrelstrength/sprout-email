<?php
namespace Craft;

use Guzzle\Http\Url;
use Symfony\Component\EventDispatcher\Tests\TestEventSubscriberWithMultipleListeners;

abstract class SproutEmailBaseMailer
{
	/**
	 * The settings for this mailer, stored in sproutemail_mailers
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Whether this mailer has been initialized
	 *
	 * @var bool
	 */
	protected $initialized = false;

	public function init()
	{
		$this->settings    = sproutEmail()->mailers->getSettingsByMailerName($this->getId());
		$this->initialized = true;
	}

	/**
	 * @return bool
	 */
	public function isInitialized()
	{
		return $this->initialized;
	}

	/**
	 * Returns the mailer title when used in string context
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->getTitle();
	}

	/**
	 * Returns the service id to use as the formal identifier for all mailers
	 *
	 * @example sproutemail
	 *
	 * @return string
	 */
	final public function getId()
	{
		return preg_replace('/[^a-z]+/i', '', strtolower($this->getName()));
	}

	final public function getCpTitle()
	{
		$t = $this->getTitle();

		if ($this->hasCpSection())
		{
			$t = sprintf('<a href="%s/%s" title="%s">%s</a>', UrlHelper::getCpUrl('sproutemail'), $this->getId(), $t, $t);
			$t = TemplateHelper::getRaw($t);
		}

		return $t;
	}

	/**
	 * Returns the qualified service name
	 *
	 * @todo    Consider removing once all templates are using proper settings handling
	 *
	 * @example SproutEmail
	 *
	 * @return string
	 */
	public function getName()
	{
	}

	/**
	 * Returns the service title to use when displaying a label or similar use case
	 *
	 * @example Sprout Email Service
	 *
	 * @return string
	 */
	public function getTitle()
	{
	}

	/**
	 * Returns whether a plugin needs to display a tab within Sprout Email to accomplish their tasks
	 * @return bool
	 */
	public function hasCpSection()
	{
		return false;
	}

	/**
	 * Returns a short description of this mailer
	 *
	 * @example The Sprout Email services uses the Craft API to send emails
	 *
	 * @return string
	 */
	public function getDescription()
	{
	}

	/**
	 * Returns the settings for this mailer
	 *
	 * @return array
	 */
	public function getSettings()
	{
		if (!$this->isInitialized())
		{
			$this->init();
		}

		return $this->settings;
	}

	/**
	 * Returns the settings and default values that should be stored when first installed
	 *
	 * @return array
	 */
	public function getDefaultSettings()
	{
		return array();
	}

	/**
	 * Returns a rendered html string to use for capturing settings
	 *
	 * @return string
	 */
	public function getSettingsHtml()
	{
	}

	/**
	 * Returns the value that should be saved to settings for this mailer
	 *
	 * @example
	 * return craft()->request->getPost('sproutemail');
	 *
	 * @return mixed
	 */
	public function prepareSettings()
	{
		return craft()->request->getPost($this->getId());
	}

	/**
	 * Gives the service a chance to attach the value to the right field id before outputting it
	 *
	 * @param $value
	 *
	 * @return mixed
	 */
	public function prepareValue($value)
	{
		return $value;
	}

	public function saveRecipientList(SproutEmail_CampaignModel $campaign)
	{
	}

	public function sendEntry(SproutEmail_Campaign $campaign)
	{
	}

	public function exportEntry(SproutEmail_Campaign $campaign)
	{
	}

	public function prepareRecipientLists(SproutEmail_EntryModel $entry, SproutEmail_CampaignModel $campaign)
	{
	}

	public function getRecipientLists()
	{
		return array();
	}

	public function getRecipientListsHtml()
	{
		return TemplateHelper::getRaw('<p>'.Craft::t('This service does not require recipient.').'</p>');
	}
}
