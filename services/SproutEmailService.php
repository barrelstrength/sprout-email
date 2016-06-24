<?php
namespace Craft;

/**
 * Class SproutEmailService
 *
 * @package Craft
 *
 * @property SproutEmail_MailerService        $mailers
 * @property SproutEmail_EntriesService       $entries
 * @property SproutEmail_CampaignsService     $campaigns
 * @property SproutEmail_NotificationsService $notifications
 * @property SproutEmail_DefaultMailerService $defaultmailer
 * @property SproutEmail_SentEmailsService    $sentEmails
 */
class SproutEmailService extends BaseApplicationComponent
{
	public $mailers;
	public $entries;
	public $campaigns;
	public $notifications;
	public $defaultmailer;
	public $sentEmails;
	public $notificationemail;
	private $error = '';

	public function init()
	{
		parent::init();

		$this->mailers       = Craft::app()->getComponent('sproutEmail_mailer');
		$this->defaultmailer = Craft::app()->getComponent('sproutEmail_defaultMailer');
		$this->entries       = Craft::app()->getComponent('sproutEmail_entries');
		$this->campaigns     = Craft::app()->getComponent('sproutEmail_campaigns');
		$this->notifications = Craft::app()->getComponent('sproutEmail_notifications');
		$this->sentEmails    = Craft::app()->getComponent('sproutEmail_sentEmails');
		$this->notificationemail = Craft::app()->getComponent('sproutEmail_notificationEmail');
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
	 * @param string $str
	 * @param object $obj
	 *
	 * @return string
	 */
	public function renderObjectTemplateSafely($str, $obj)
	{
		try
		{
			return craft()->templates->renderObjectTemplate($str, $obj);
		}
		catch (\Exception $e)
		{
			$this->error($e->getMessage(), 'template');
		}
	}

	/**
	 * @param BaseModel   $model
	 * @param array|mixed $obj
	 */
	public function renderObjectContentSafely(&$model, $obj)
	{
		$content = $model->getContent();

		foreach ($content as $attribute => $value)
		{
			if (is_string($value) && stripos($value, '{') !== false)
			{
				$model->getContent()->{$attribute} = $this->renderObjectTemplateSafely($value, $obj);
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

		// @todo - can't explain this
		// If a blank template is passed in, Craft renders the index template
		// If a template is set specifically to the value `test` Craft also
		// appears to render the index template.
		if (empty($template))
		{
			return $renderedTemplate;
		}

		$oldPath = craft()->path->getTemplatesPath();
		craft()->path->setTemplatesPath(craft()->path->getSiteTemplatesPath());

		try
		{
			$renderedTemplate = craft()->templates->render($template, $variables);
		}
		catch (\Exception $e)
		{
			$this->error($e->getMessage(), 'template');
		}

		craft()->path->setTemplatesPath($oldPath);

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
		$path = craft()->path->getTemplatesPath();

		craft()->path->setTemplatesPath(craft()->path->getSiteTemplatesPath());

		$exists = craft()->templates->doesTemplateExist($template);

		craft()->path->setTemplatesPath($path);

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
	 * @param mixed $msg
	 * @param array $vars
	 */
	public function info($msg, array $vars = array())
	{
		if (is_string($msg))
		{
			$msg = Craft::t($msg, $vars);
		}
		else
		{
			$msg = print_r($msg, true);
		}

		SproutEmailPlugin::log($msg, LogLevel::Info);
	}

	/**
	 * Logs an error in cases where it makes more sense than to throw an exception
	 *
	 * @param mixed $msg
	 * @param array $vars
	 */
	public function error($msg, $key = '', array $vars = array())
	{
		if (is_string($msg))
		{
			$msg = Craft::t($msg, $vars);
		}
		else
		{
			$msg = print_r($msg, true);
		}

		if (!empty($key))
		{
			$this->error[$key] = $msg;
		}
		else
		{
			$this->error = $msg;
		}

		SproutEmailPlugin::log($msg, LogLevel::Error);
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
	 * Handles event to attach files to email
	 *
	 * @param Event $event
	 */

	public function handleOnBeforeSendEmail(Event $event)
	{
		$variables = $event->params['variables'];

		// Make sure this is a Sprout Email Event
		if (!isset($variables['sproutEmailEntry']))
		{
			return true;
		}

		$entry                 = $variables['sproutEmailEntry'];
		$enableFileAttachments = $entry->enableFileAttachments;

		if (isset($variables['elementEntry']) && $enableFileAttachments)
		{
			$entry = $variables['elementEntry'];

			/**
			 * @var $field FieldModel
			 */
			if (method_exists($entry->getFieldLayout(), 'getFields'))
			{
				foreach ($entry->getFieldLayout()->getFields() as $fieldLayoutField)
				{
					$field = $fieldLayoutField->getField();
					$type  = $field->getFieldType();

					if (get_class($type) === 'Craft\\AssetsFieldType')
					{
						$this->attachAsset($entry, $field, $event);
					}
					// @todo validate assets within MatrixFieldType
				}
			}
		}

		if (isset($variables['elementEntry']) && !$enableFileAttachments)
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
	 * @param array $vars
	 */
	public function log($message, array $vars = array())
	{
		if (is_string($message))
		{
			$message = Craft::t($message, $vars);
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
		return $asset->getSource()->getSourceType()->getBasePath() . $asset->getFolder()->path . $asset->filename;
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
	 * Return true if has permission
	 *
	 * @return bool
	 */
	public function checkPermission()
	{
		$user = craft()->userSession->getUser();

		if (!$user->can('editSproutEmailSettings'))
		{
			return false;
		}

		return true;
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
		try
		{
			return craft()->email->sendEmail($emailModel, $variables);
		}
		catch (\Exception $e)
		{
			$message = $e->getMessage();

			sproutEmail()->error($message);

			$this->handleOnSendEmailErrorEvent($message, $emailModel, $variables);

			return false;
		}
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

		// Add a few additional variables to our info table
		$event->params['variables']['info']->deliveryStatus = $deliveryStatus;
		$event->params['variables']['info']->message        = $message;

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
	public function onSendCampaign(Event $event)
	{
		$this->raiseEvent('onSendCampaign', $event);
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
}
