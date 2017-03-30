<?php
namespace Craft;

/**
 * Class SproutEmailService
 *
 * @package Craft
 *
 * @property SproutEmail_MailerService             $mailers
 * @property SproutEmail_CampaignTypesService      $campaignTypes
 * @property SproutEmail_CampaignEmailsService     $campaignEmails
 * @property SproutEmail_NotificationEmailsService $notificationEmails
 * @property SproutEmail_DefaultMailerService      $defaultMailer
 * @property SproutEmail_SentEmailsService         $sentEmails
 */
class SproutEmailService extends BaseApplicationComponent
{
	public $mailers;
	public $campaignTypes;
	public $campaignEmails;
	public $notificationEmails;
	public $sentEmails;

	private $error = '';

	public function init()
	{
		parent::init();

		$this->mailers            = Craft::app()->getComponent('sproutEmail_mailer');
		$this->campaignEmails     = Craft::app()->getComponent('sproutEmail_campaignEmails');
		$this->campaignTypes      = Craft::app()->getComponent('sproutEmail_campaignTypes');
		$this->notificationEmails = Craft::app()->getComponent('sproutEmail_notificationEmails');
		$this->sentEmails         = Craft::app()->getComponent('sproutEmail_sentEmails');
	}

	/**
	 * Returns a config value from general.php for the sproutEmail array
	 *
	 * @param string     $name
	 * @param mixed|null $default
	 *
	 * @return null
	 */
	public function getConfig($name, $default = null)
	{
		$configs = craft()->config->get('sproutEmail');

		return is_array($configs) && isset($configs[$name]) ? $configs[$name] : $default;
	}

	/**
	 * Allows us to render an object template without creating a fatal error
	 *
	 * @param string $string
	 * @param object $object
	 *
	 * @return string
	 */
	public function renderObjectTemplateSafely($string, $object)
	{
		try
		{
			return craft()->templates->renderObjectTemplate($string, $object);
		}
		catch (\Exception $e)
		{
			$this->error(Craft::t('Cannot render template. Check template file and object variables.'), 'template');
		}
	}

	/**
	 * @param BaseModel   $model
	 * @param array|mixed $object
	 */
	public function renderObjectContentSafely(&$model, $object)
	{
		$content = $model->getContent();

		foreach ($content as $attribute => $value)
		{
			if (is_string($value) && stripos($value, '{') !== false)
			{
				$model->getContent()->{$attribute} = $this->renderObjectTemplateSafely($value, $object);
			}
		}
	}

	/**
	 * Renders a site template when using it in control panel context
	 *
	 * @param string $template
	 * @param array  $variables
	 *
	 * @return null|string
	 */
	public function renderSiteTemplateIfExists($template, array $variables = array())
	{
		$renderedTemplate = null;

		// @todo - look into how to explain this
		// If a blank template is passed in, Craft renders the index template
		// If a template is set specifically to the value `test` Craft also
		// appears to render the index template.
		if (empty($template))
		{
			return $renderedTemplate;
		}

		$oldPath = craft()->templates->getTemplatesPath();
		craft()->templates->setTemplatesPath(craft()->path->getSiteTemplatesPath());

		try
		{
			$renderedTemplate = craft()->templates->render($template, $variables);
		}
		catch (\Exception $e)
		{
			// Specify template .html if no .txt
			$message = $e->getMessage();

			if (strpos($template, '.txt') === false)
			{
				$message = str_replace($template, $template . '.html', $message);
			}

			// @todo - update error handling
			$this->error($message, 'template-' . $template);
		}

		craft()->templates->setTemplatesPath($oldPath);

		return $renderedTemplate;
	}

	/**
	 * Returns whether or not a site template exists
	 *
	 * @param $template
	 *
	 * @return bool
	 */
	public function doesSiteTemplateExist($template)
	{
		$path = craft()->templates->getTemplatesPath();

		craft()->templates->setTemplatesPath(craft()->path->getSiteTemplatesPath());

		$exists = craft()->templates->doesTemplateExist($template);

		craft()->templates->setTemplatesPath($path);

		return $exists;
	}

	/**
	 * Outputs JSON encoded data to standard output stream, useful during AJAX requests
	 *
	 * @param array $variables
	 */
	public function returnJson($variables = array())
	{
		JsonHelper::sendJsonHeaders();

		// Output it into a buffer, in case TasksService wants to close the connection prematurely
		ob_start();
		echo JsonHelper::encode($variables);

		craft()->end();
	}

	/**
	 * Logs an info message to the plugin logs
	 *
	 * @param mixed $message
	 * @param array $variables
	 */
	public function info($message, array $variables = array())
	{
		if (is_string($message))
		{
			$message = Craft::t($message, $variables);
		}
		else
		{
			$message = print_r($message, true);
		}

		SproutEmailPlugin::log($message, LogLevel::Info);
	}

	/**
	 * Logs an error in cases where it makes more sense than to throw an exception
	 *
	 * @param mixed $message
	 * @param array $variables
	 */
	public function error($message, $key = '', array $variables = array())
	{
		if (is_string($message))
		{
			$message = Craft::t($message, $variables);
		}
		else
		{
			$message = print_r($message, true);
		}

		if (!empty($key))
		{
			$this->error[$key] = $message;
		}
		else
		{
			$this->error = $message;
		}

		SproutEmailPlugin::log($message, LogLevel::Error);
	}

	/**
	 * @return mixed error
	 */
	public function getError($key = '')
	{
		if (!empty($key) && isset($this->error[$key]))
		{
			return $this->error[$key];
		}
		else
		{
			return $this->error;
		}
	}

	/**
	 * @param string $text
	 *
	 * @return string
	 */
	public function createHandle($text)
	{
		$text  = ElementHelper::createSlug($text);
		$words = explode('-', $text);
		$start = array_shift($words);

		if (count($words))
		{
			foreach ($words as $word)
			{
				$start = $start . ucfirst($word);
			}
		}

		return $start;
	}

	/**
	 * Attach files during Craft onBeforeSendEmail event
	 *
	 * @param Event $event
	 *
	 * @return bool
	 */
	public function handleOnBeforeSendEmail(Event $event)
	{
		$variables = $event->params['variables'];

		// Make sure this is a Sprout Email Event
		if (!isset($variables['email']) ||
			(isset($variables['email']) && !get_class($variables['email']) === 'Craft\\SproutEmail_NotificationEmailModel'))
		{
			return true;
		}

		$notificationEmail     = $variables['email'];
		$enableFileAttachments = $notificationEmail->enableFileAttachments;

		if (isset($variables['object']) && $enableFileAttachments)
		{
			$eventObject = $variables['object'];

			/**
			 * @var $field FieldModel
			 */
			if (method_exists($eventObject->getFieldLayout(), 'getFields'))
			{
				foreach ($eventObject->getFieldLayout()->getFields() as $fieldLayoutField)
				{
					$field = $fieldLayoutField->getField();
					$type  = $field->getFieldType();

					if (get_class($type) === 'Craft\\AssetsFieldType')
					{
						$this->attachAsset($eventObject, $field, $event);
					}
					// @todo validate assets within MatrixFieldType
				}
			}
		}

		if (isset($variables['object']) && !$enableFileAttachments)
		{
			$this->log('File attachments are currently not enabled for Sprout Email.');
		}
	}

	private function attachAsset($entry, $field, $event)
	{
		/**
		 * @var $criteria ElementCriteriaModel
		 */
		$criteria = $entry->{$field->handle};

		if ($criteria instanceof ElementCriteriaModel)
		{
			$assets = $criteria->find();

			if ($assets)
			{
				$this->attachAssetFilesToEmailModel($event->params['emailModel'], $assets);
			}
		}
	}

	/**
	 * @param mixed $message
	 * @param array $variables
	 */
	public function log($message, array $variables = array())
	{
		if (is_string($message))
		{
			$message = Craft::t($message, $variables);
		}
		else
		{
			$message = print_r($message, true);
		}

		SproutEmailPlugin::log($message, LogLevel::Info);
	}

	/**
	 * @param EmailModel       $email
	 * @param AssetFileModel[] $assets
	 */
	protected function attachAssetFilesToEmailModel(EmailModel $email, array $assets)
	{
		foreach ($assets as $asset)
		{
			$name = $asset->filename;

			$path = $this->getAssetFilePath($asset);

			$email->addAttachment($path, $name);
		}
	}

	/**
	 * @param AssetFileModel $asset
	 *
	 * @return string
	 */
	protected function getAssetFilePath(AssetFileModel $asset)
	{
		$imageSourcePath = $asset->getSource()->getSourceType()->getImageSourcePath($asset);

		return $imageSourcePath;
	}

	/**
	 * Returns whether or not the templates directory is writable
	 *
	 * @return bool
	 */
	public function canCreateExamples()
	{
		return is_writable(craft()->path->getSiteTemplatesPath());
	}

	/**
	 * Check if example already exist
	 *
	 * @return bool
	 */
	public function hasExamples()
	{
		$path = craft()->path->getSiteTemplatesPath() . 'sproutemail';

		if (file_exists($path))
		{
			return true;
		}

		return false;
	}

	/**
	 * @param $subject
	 *
	 * @return string
	 */
	public function encodeSubjectLine($subject)
	{
		return '=?UTF-8?B?' . base64_encode($subject) . '?=';
	}

	public function sendEmail(EmailModel $emailModel, $variables = array())
	{
		$errorMessage = $this->getError();

		if (!empty($errorMessage))
		{
			if (is_array($errorMessage))
			{
				$errorMessage = implode("\n", $errorMessage);
			}

			$this->handleOnSendEmailErrorEvent($errorMessage, $emailModel, $variables);

			return false;
		}

		return craft()->email->sendEmail($emailModel, $variables);
	}

	/**
	 * @param EmailModel $emailModel
	 * @param            $campaign
	 * @param            $email
	 * @param            $object
	 *
	 * @return bool|EmailModel
	 */
	public function renderEmailTemplates(EmailModel $emailModel, $template = '', $notification, $object)
	{
		// Render Email Entry fields that have dynamic values
		$emailModel->subject   = sproutEmail()->renderObjectTemplateSafely($notification->subjectLine, $object);
		$emailModel->fromName  = sproutEmail()->renderObjectTemplateSafely($notification->fromName, $object);
		$emailModel->fromEmail = sproutEmail()->renderObjectTemplateSafely($notification->fromEmail, $object);
		$emailModel->replyTo   = sproutEmail()->renderObjectTemplateSafely($notification->replyToEmail, $object);

		// Render the email templates
		$emailModel->body     = sproutEmail()->renderSiteTemplateIfExists($template . '.txt', array(
			'email'        => $notification,
			'object'       => $object,

			// @deprecate in v3 in favor of the `email` variable
			'entry'        => $notification,
			'notification' => $notification
		));
		$emailModel->htmlBody = sproutEmail()->renderSiteTemplateIfExists($template, array(
			'email'        => $notification,
			'object'       => $object,

			// @deprecate in v3 in favor of the `email` variable
			'entry'        => $notification,
			'notification' => $notification
		));

		$styleTags = array();

		$htmlBody = $this->addPlaceholderStyleTags($emailModel->htmlBody, $styleTags);

		// Some Twig code in our email fields may need us to decode
		// entities so our email doesn't throw errors when we try to
		// render the field objects. Example: {variable|date("Y/m/d")}
		$emailModel->body = HtmlHelper::decode($emailModel->body);
		$htmlBody         = HtmlHelper::decode($htmlBody);

		// Process the results of the template s once more, to render any dynamic objects used in fields
		$emailModel->body     = sproutEmail()->renderObjectTemplateSafely($emailModel->body, $object);
		$emailModel->htmlBody = sproutEmail()->renderObjectTemplateSafely($htmlBody, $object);

		$emailModel->htmlBody = $this->removePlaceholderStyleTags($emailModel->htmlBody, $styleTags);

		// @todo - update error handling
		$templateError = sproutEmail()->getError('template');

		if (!empty($templateError))
		{
			$emailModel->htmlBody = $templateError;
		}

		return $emailModel;
	}

	/**
	 * Add placeholder style tags to avoid CSS brackets conflicting with Craft object syntax shorthand i.e. {title}
	 *
	 * @param $htmlBody
	 * @param $styleTags
	 *
	 * @return mixed
	 */
	public function addPlaceholderStyleTags($htmlBody, &$styleTags)
	{
		// Get the style tag
		preg_match_all("/<style\\b[^>]*>(.*?)<\\/style>/s", $htmlBody, $matches);

		$results = array();

		if (!empty($matches))
		{
			$tags = $matches[0];

			// Temporarily replace with style tags with a random string
			if (!empty($tags))
			{
				$i = 0;
				foreach ($tags as $tag)
				{
					$key = "<!-- %style$i% -->";

					$styleTags[$key] = $tag;

					$htmlBody = str_replace($tag, $key, $htmlBody);

					$i++;
				}
			}
		}

		return $htmlBody;
	}

	/**
	 * Put back the style tag after object is rendered if style tag is found
	 *
	 * @param $htmlBody
	 * @param $styleTags
	 *
	 * @return mixed
	 */
	public function removePlaceholderStyleTags($htmlBody, $styleTags)
	{
		if (!empty($styleTags))
		{
			foreach ($styleTags as $key => $tag)
			{
				$htmlBody = str_replace($key, $tag, $htmlBody);
			}
		}

		return $htmlBody;
	}

	public function getValidAndInvalidRecipients($recipients)
	{
		$invalidRecipients = array();
		$validRecipients   = array();
		$emails            = array();

		if (!empty($recipients))
		{
			$recipients = explode(",", $recipients);

			foreach ($recipients as $recipient)
			{
				$email    = trim($recipient);
				$emails[] = $email;

				if (filter_var($email, FILTER_VALIDATE_EMAIL) === false)
				{
					$invalidRecipients[] = $email;
				}
				else
				{
					$recipientEmail = SproutEmail_SimpleRecipientModel::create(array(
						'email' => $email
					));

					$validRecipients[] = $recipientEmail;
				}
			}
		}

		return array(
			'valid'   => $validRecipients,
			'invalid' => $invalidRecipients,
			'emails'  => $emails
		);
	}

	public function handleOnSendEmailErrorEvent($message, EmailModel $emailModel, $variables = array())
	{
		$user = craft()->users->getUserByEmail($emailModel->toEmail);

		if (!$user)
		{
			$user            = new UserModel();
			$user->email     = $emailModel->toEmail;
			$user->firstName = $emailModel->toFirstName;
			$user->lastName  = $emailModel->toLastName;
		}

		// Call Email service class instead of $this to get sender settings
		$emailService = new EmailService;

		$event = new Event($emailService, array(
			'user'           => $user,
			'emailModel'     => $emailModel,
			'variables'      => $variables,
			'message'        => $message,

			// Set this here so we can set the status properly when saving
			'deliveryStatus' => 'failed',
		));

		$this->onSendEmailError($event);
	}

	/**
	 * Prepare error data and log sent email
	 *
	 * @param Event $event
	 */
	public function handleLogSentEmailOnSendEmailError(Event $event)
	{
		$deliveryStatus = (isset($event->params['deliveryStatus'])) ? $event->params['deliveryStatus'] : null;
		$message        = (isset($event->params['message'])) ? $event->params['message'] : Craft::t("Unknown error");

		if (isset($event->params['variables']['info']))
		{
			// Add a few additional variables to our info table
			$event->params['variables']['info']->deliveryStatus = $deliveryStatus;
			$event->params['variables']['info']->message        = $message;
		}
		else
		{
			// This is for logging errors before sproutEmail()->sendEmail is called.
			$infoTable = new SproutEmail_SentEmailInfoTableModel();

			$infoTable->deliveryStatus = $deliveryStatus;
			$infoTable->message        = $message;

			$event->params['variables']['info'] = $infoTable;
		}

		if (isset($event->params['variables']['info']))
		{
			// Add a few additional variables to our info table
			$event->params['variables']['info']->deliveryStatus = $deliveryStatus;
			$event->params['variables']['info']->message        = $message;
		}
		else
		{
			// This is for logging errors before sproutEmail()->sendEmail is called.
			$infoTable = new SproutEmail_SentEmailInfoTableModel();

			$infoTable->deliveryStatus = $deliveryStatus;
			$infoTable->message        = $message;

			$event->params['variables']['info'] = $infoTable;
		}

		sproutEmail()->sentEmails->logSentEmail($event);
	}

	/**
	 * On Send Email Error Event
	 *
	 * @param Event $event
	 *
	 * @throws \CException
	 */
	public function onSendEmailError(Event $event)
	{
		$this->raiseEvent('onSendEmailError', $event);
	}

	/**
	 * On Send Campaign Event
	 *
	 * @param Event $event
	 *
	 * @throws \CException
	 */
	public function onSendSproutEmail(Event $event)
	{
		$this->raiseEvent('onSendSproutEmail', $event);
	}

	/**
	 * On Set Status Event
	 *
	 * @param Event $event
	 *
	 * @throws \CException
	 */
	public function onSetStatus(Event $event)
	{
		$this->raiseEvent('onSetStatus', $event);
	}

	/**
	 * @param mixed|null $element
	 *
	 * @throws \Exception
	 * @return array|string
	 */
	public function getRecipients($element = null, $model)
	{
		$recipientsString = $model->getAttribute('recipients');

		// Possibly called from entry edit screen
		if (is_null($element))
		{
			return $recipientsString;
		}

		// Previously converted to array somehow?
		if (is_array($recipientsString))
		{
			return $recipientsString;
		}

		// Previously stored as JSON string?
		if (stripos($recipientsString, '[') === 0)
		{
			return JsonHelper::decode($recipientsString);
		}

		// Still a string with possible twig generator code?
		if (stripos($recipientsString, '{') !== false)
		{
			try
			{
				$recipients = craft()->templates->renderObjectTemplate(
					$recipientsString,
					$element
				);

				return array_unique(ArrayHelper::filterEmptyStringsFromArray(ArrayHelper::stringToArray($recipients)));
			}
			catch (\Exception $e)
			{
				throw $e;
			}
		}

		// Just a regular CSV list
		if (!empty($recipientsString))
		{
			return ArrayHelper::filterEmptyStringsFromArray(ArrayHelper::stringToArray($recipientsString));
		}

		return array();
	}

	public function getFirstAvailableTab()
	{
		$settings = craft()->plugins->getPlugin('sproutemail')->getSettings();

		switch (true)
		{
			case $settings->enableCampaignEmails:
				return 'sproutemail/campaigns';

			case $settings->enableNotificationEmails:
				return 'sproutemail/notifications';

			case $settings->enableSentEmails:
				return 'sproutemail/sentemails';

			default:
				return 'sproutemail/settings';
		}
	}

	/**
	 * Use for populating template "_includes/tabs"
	 *
	 * @param BaseModel $model
	 * @param           $model
	 *
	 * @return array
	 */
	public function getModelTabs(BaseModel $model)
	{
		$tabs = array();

		$campaignEmailTabs = $model->getFieldLayout()->getTabs();

		if (!empty($campaignEmailTabs))
		{
			foreach ($campaignEmailTabs as $index => $tab)
			{
				// Do any of the fields on this tab have errors?
				$hasErrors = false;

				if ($model->hasErrors())
				{
					foreach ($tab->getFields() as $field)
					{
						if ($model->getErrors($field->getField()->handle))
						{
							$hasErrors = true;
							break;
						}
					}
				}

				$tabs[] = array(
					'label' => Craft::t($tab->name),
					'url'   => '#tab' . ($index + 1),
					'class' => ($hasErrors ? 'error' : null)
				);
			}
		}

		return $tabs;
	}
}
