<?php
namespace Craft;

/**
 * Class SproutEmailService
 *
 * @package Craft
 *
 * @property SproutEmail_MailerService           $mailers
 * @property SproutEmail_EntriesService          $entries
 * @property SproutEmail_CampaignsService        $campaigns
 * @property SproutEmail_NotificationsService    $notifications
 */
class SproutEmailService extends BaseApplicationComponent
{
	public  $mailers;
	public  $entries;
	public  $campaigns;
	public  $notifications;
	public  $defaultmailer;
	public  $sentemails;

	private $error = '';

	public function init()
	{
		parent::init();

		$this->mailers       = Craft::app()->getComponent('sproutEmail_mailer');
		$this->defaultmailer = Craft::app()->getComponent('sproutEmail_defaultMailer');
		$this->entries       = Craft::app()->getComponent('sproutEmail_entries');
		$this->campaigns     = Craft::app()->getComponent('sproutEmail_campaigns');
		$this->notifications = Craft::app()->getComponent('sproutEmail_notifications');
		$this->sentemails    = Craft::app()->getComponent('sproutEmail_sentEmails');
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
			$this->error($e->getMessage());
		}
	}

	/**
	 * @param BaseModel $model
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
	 * @param array $variables
	 *
	 * @return null|string
	 */
	public function renderSiteTemplateIfExists($template, array $variables = array())
	{
		$rendered = null;

		// If blank template is passed in, Craft will render the index template for some odd reason
		if (!empty($template))
		{
			$path = craft()->path->getTemplatesPath();

			craft()->path->setTemplatesPath(craft()->path->getSiteTemplatesPath());

			try
			{
				$rendered = craft()->templates->render($template, $variables);
			}
			catch (\Exception $e)
			{
				$this->error($e->getMessage());
			}

			craft()->path->setTemplatesPath($path);
		}

		return $rendered;
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
	public function error($msg, array $vars = array())
	{
		if (is_string($msg))
		{
			$msg = Craft::t($msg, $vars);
		}
		else
		{
			$msg = print_r($msg, true);
		}

		SproutEmailPlugin::log($msg, LogLevel::Error);
	}

	/**
	 * @param string $text
	 *
	 * @return string
	 */
	public function createHandle($text)
	{
		$text = ElementHelper::createSlug($text);
		$words = explode('-', $text);
		$start = array_shift($words);

		if (count($words))
		{
			foreach ($words as $word)
			{
				$start = $start.ucfirst($word);
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
		$variables             = $event->params['variables'];

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
			if(method_exists($entry->getFieldLayout(), 'getFields'))
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
			if($assets)
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
		return $asset->getSource()->getSourceType()->getBasePath().$asset->getFolder()->path.$asset->filename;
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
	 * @return bool
	 */
	public function hasExamples()
	{
		$path = craft()->path->getSiteTemplatesPath() . 'sproutemail';
		if(file_exists($path))
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

		if(!$user->can('editSproutEmailSettings'))
		{
			return false;
		}

		return true;
	}

	/**
	 * @param $subject
	 * @return string
	 */
	public function encodeSubjectLine($subject)
	{
		return '=?UTF-8?B?'.base64_encode($subject).'?=';
	}

	public function test()
	{
		echo "test is siis";
	}
}
