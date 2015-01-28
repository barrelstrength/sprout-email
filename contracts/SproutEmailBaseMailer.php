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
	 * Initializes the mailer by fetching its settings and loading necessary configs
	 */
	public function init()
	{
		if (!$this->initialized)
		{
			$this->settings    = sproutEmail()->mailers->getSettingsByMailerName($this->getId());
			$this->installed   = sproutEmail()->mailers->isInstalled($this->getId());
			$this->initialized = true;
		}
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
	 * Returns whether or not the mailer has been properly initialized by Sprout Email
	 *
	 * @return bool
	 */
	public function isInitialized()
	{
		return $this->initialized;
	}

	/**
	 * Returns whether or not the mailer has been installed and additionally registered with Sprout Email
	 *
	 * @return bool
	 */
	public function isInstalled()
	{
		$this->init();

		return $this->installed;
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
	 * Returns the qualified mailer name
	 *
	 * @example CoreMailer
	 *
	 * @return string
	 */
	abstract public function getName();

	/**
	 * Returns the mailer title to use when displaying a label or similar use case
	 *
	 * @example Sprout Email Service
	 *
	 * @return string
	 */
	abstract public function getTitle();

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
		if ($this->hasCpSection())
		{
			return sproutEmail()->mailers->getMailerCpSectionLink($this);
		}

		return $this->getTitle();
	}

	/**
	 * Returns the name of the plugin this mailer is associated with
	 *
	 * @return string
	 */
	public function getPluginName()
	{
	}

	/**
	 * Returns whether or not the mailer has registered routes to accomplish tasks within Sprout Email
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
	abstract public function getDescription();

	/**
	 * Returns the settings model for this mailer
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
	 * @see        defineSettings()
	 *
	 * @return array
	 */
	public function getDefaultSettings()
	{
		craft()->deprecator->log(__FUNCTION__, 'Deprecated since version 0.8.2 in favor of defineSettings()');

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
	 * Returns a list of recipients from the mailer to be used primarily by getRecipientListsHtml()
	 *
	 * @return array
	 */
	public function getRecipientLists()
	{
		return array();
	}

	/**
	 * Returns a rendered string with inputs to select a recipient lists if available/required by this mailer
	 *
	 * @return \Twig_Markup
	 */
	public function getRecipientListsHtml()
	{
		return TemplateHelper::getRaw('<p>'.Craft::t('This mailer does not require recipients.').'</p>');
	}

	/**
	 * Gives a mailer the ability to register an action to post to when a [prepare] modal is launched
	 *
	 * @return string
	 */
	public function getActionForPrepareModal()
	{
		return 'sproutEmail/mailer/getPrepareModal';
	}

	/**
	 * Gives a mailer the ability to register an action to post to when a [preview] modal is launched
	 *
	 * @deprecated Deprecated since version 0.8.3 in favor of using ElementTypeModel::routeRequestForMatchedElement()
	 *
	 * @return string
	 */
	public function getActionForPreviewModal()
	{
		craft()->deprecator->log(__FUNCTION__, 'Deprecated since version 0.8.3 in favor of using ElementTypeModel::routeRequestForMatchedElement()');

		return 'sproutEmail/mailer/getPreviewModal';
	}

	/**
	 * Gives mailers the ability to include their own modal resources and register their dynamic action handlers
	 *
	 * @example
	 * Mailers should be calling the following functions from within their implementation
	 *
	 * craft()->templates->includeJs(File|Resource)();
	 * craft()->templates->includeCss(File|Resource)();
	 *
	 * @note
	 * To register a dynamic action handler, mailers should listen for sproutEmailBeforeRender
	 * $(document).on('sproutEmailBeforeRender', function(e, content) {});
	 */
	public function includeModalResources()
	{
	}

	/**
	 * Enables mailers to define their own settings and validation for them
	 *
	 * @return array
	 */
	public function defineSettings()
	{
		return array();
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
	 * @todo Decide how this should be standardised across mailers
	 *
	 * @param SproutEmail_CampaignModel $campaign
	 */
	public function saveRecipientList(SproutEmail_CampaignModel $campaign)
	{
	}

	/**
	 * Gives a mailer the responsibility to send campaigns if they implement SproutEmailCampaignSenderInterface
	 *
	 * @param SproutEmail_EntryModel    $entry
	 * @param SproutEmail_CampaignModel $campaign
	 */
	public function sendCampaign(SproutEmail_EntryModel $entry, SproutEmail_CampaignModel $campaign)
	{
	}

	/**
	 * Gives a mailer the responsibility to send notifications if they implement SproutEmailNotificationSenderInterface
	 *
	 * @param SproutEmail_EntryModel    $entry
	 * @param SproutEmail_CampaignModel $campaign
	 */
	public function sendNotification(SproutEmail_EntryModel $entry, SproutEmail_CampaignModel $campaign)
	{
	}

	/**
	 * @deprecated Deprecated since version 0.8.3 in favor of sendCampaign()
	 *
	 * @param SproutEmail_Campaign $campaign
	 */
	public function sendEntry(SproutEmail_Campaign $campaign)
	{
		craft()->deprecator->log(__FUNCTION__, 'Deprecated since version 0.8.3 in favor of using ElementTypeModel::routeRequestForMatchedElement()');
	}

	/**
	 * @deprecated Deprecated since version 0.8.3 in favor of sendCampaign()
	 *
	 * @param SproutEmail_Campaign $campaign
	 */
	public function exportEntry(SproutEmail_Campaign $campaign)
	{
		craft()->deprecator->log(__FUNCTION__, 'Deprecated since version 0.8.3 in favor of using ElementTypeModel::routeRequestForMatchedElement()');
	}
}
