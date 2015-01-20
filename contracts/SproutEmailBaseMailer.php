<?php
namespace Craft;

/**
 * The official mailer API for third party email service providers to implement
 *
 * Class SproutEmailBaseMailer
 *
 * @package Craft
 */
abstract class SproutEmailBaseMailer
{
	/**
	 * The settings for this mailer stored in the sproutemail_mailers table
	 *
	 * @var Model
	 */
	protected $settings;

	/**
	 * Whether this mailer has been initialized
	 *
	 * @var bool
	 */
	protected $initialized = false;

	/**
	 * Whether this mailer has been installed
	 *
	 * @var bool
	 */
	protected $installed = false;

	/**
	 * Initializes that plugin by fetching its settings and setting needed configs
	 */
	public function init()
	{
		$this->settings    = sproutEmail()->mailers->getSettingsByMailerName($this->getId());
		$this->installed   = sproutEmail()->mailers->isInstalled($this->getId());
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
	 * @return bool
	 */
	public function isInstalled()
	{
		$this->init();

		return $this->installed;
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
	 * Returns the mailer id to use as the formal identifier
	 *
	 * @example sproutemail
	 *
	 * @return string
	 */
	final public function getId()
	{
		return preg_replace('/[^a-z0-9]+/i', '', StringHelper::toLowerCase($this->getName()));
	}

	/**
	 * Returns the mailer title optionally wrapped in a link pointing to /sproutemail/mailer
	 *
	 * @note
	 * We use this to integrate third party plugin routes and UI seamlessly within Sprout Email
	 *
	 * @see hasCpSection()
	 *
	 * @return string|\Twig_Markup
	 */
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
	 * Returns the qualified mailer name
	 *
	 * @example CoreMailer
	 *
	 * @return string
	 */
	public function getName()
	{
	}

	/**
	 * Returns the mailer title to use when displaying a label or similar use case
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
	 *
	 * @return bool
	 */
	public function hasCpSection()
	{
		return false;
	}

	/**
	 * Returns a short description of this mailer
	 *
	 * @example The Sprout Email Core Mailer uses the Craft API to send emails
	 *
	 * @return string
	 */
	public function getDescription()
	{
	}

	/**
	 * Returns the settings for this mailer
	 *
	 * @return Model
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
	 * @deprecated Deprecated since version 0.8.2 in favor of defineSettings()
	 *
	 * @see defineSettings()
	 *
	 * @return array
	 */
	public function getDefaultSettings()
	{
		return array();
	}

	/**
	 * Returns a rendered html string to use for capturing settings input
	 *
	 * @return string
	 */
	public function getSettingsHtml()
	{
	}

	/**
	 * Returns the value that should be saved to the settings column for this mailer
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
	 * Gives the mailer chance to attach the value to the right field id before outputting it
	 *
	 * @param $value
	 *
	 * @return mixed
	 */
	public function prepareValue($value)
	{
		return $value;
	}

	/**
	 * @todo Decide how this should be standardised across mailers
	 *
	 * @param SproutEmail_CampaignModel $campaign
	 */
	public function saveRecipientList(SproutEmail_CampaignModel $campaign)
	{
	}

	/**
	 * @todo Decide how this should be standardised across mailers
	 *
	 * @param SproutEmail_Campaign $campaign
	 */
	public function sendEntry(SproutEmail_Campaign $campaign)
	{
	}

	/**
	 * @todo Decide how this should be standardised across mailers
	 *
	 * @param SproutEmail_Campaign $campaign
	 */
	public function exportEntry(SproutEmail_Campaign $campaign)
	{
	}

	/**
	 * Returns an entry model to be stored and used by Sprout Email for sending via this mailer
	 *
	 * @todo Revise if this responsibility should fall on the mailer or the mailer service
	 *
	 * @param SproutEmail_EntryModel    $entry
	 * @param SproutEmail_CampaignModel $campaign
	 *
	 * @return SproutEmail_EntryModel
	 */
	public function prepareRecipientLists(SproutEmail_EntryModel $entry, SproutEmail_CampaignModel $campaign)
	{
		return new SproutEmail_EntryModel();
	}

	/**
	 * Returns a list of recipients from the mailer to be used primarily by getRecipientListsHtml()
	 *
	 * @return array
	 */
	public function getRecipientLists()
	{
		return array();
	}

	/**
	 * Returns an rendered string with inputs to select a recipient lists if available/require by this mailer
	 *
	 * @return \Twig_Markup
	 */
	public function getRecipientListsHtml()
	{
		return TemplateHelper::getRaw('<p>'.Craft::t('This mailer does not require recipients.').'</p>');
	}
}
