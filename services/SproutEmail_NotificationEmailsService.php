<?php
namespace Craft;

/**
 * The Notification Email API service layer
 *
 * Class SproutEmail_NotificationEmailsService
 *
 * @package Craft
 */
class SproutEmail_NotificationEmailsService extends BaseApplicationComponent
{
	/**
	 * @var SproutEmailBaseEvent[]
	 */
	protected $availableEvents;

	/**
	 * @var \Callable[] Events that notifications have subscribed to
	 */
	protected $registeredEvents = array();

	public function init()
	{
		$this->availableEvents = $this->getAvailableEvents();
	}

	/**
	 * Returns all campaign notifications based on the passed in event id
	 *
	 * @param string $eventId
	 *
	 * @return SproutEmail_NotificationModel[]|null
	 */
	public function getNotifications($eventId = null)
	{
		if ($eventId)
		{
			$attributes = array('eventId' => $eventId);

			$notifications = SproutEmail_NotificationEmailRecord::model()->findAllByAttributes($attributes);
		}
		else
		{
			$notifications = SproutEmail_NotificationEmailRecord::model()->findAll();
		}

		return SproutEmail_NotificationEmailModel::populateModels($notifications);
	}

	public function getNotificationEmailById($emailId)
	{
		return craft()->elements->getElementById($emailId, 'SproutEmail_NotificationEmail');
	}

	/**
	 * Returns all the available events that notifications can subscribe to
	 *
	 * @return array
	 */
	public function getAvailableEvents()
	{
		if (null === $this->availableEvents)
		{
			$responses = craft()->plugins->call('defineSproutEmailEvents');

			if ($responses)
			{
				foreach ($responses as $plugin => $pluginEvents)
				{
					if (is_array($pluginEvents) && count($pluginEvents))
					{
						foreach ($pluginEvents as $event)
						{
							$event->setPluginName($plugin);

							$this->availableEvents[$event->getEventId()] = $event;
						}
					}
				}
			}

			if (count($this->availableEvents))
			{
				uasort($this->availableEvents, function ($a, $b)
				{
					return strlen($a->getEventAction()) > strlen($b->getEventAction());
				});
			}
		}

		return $this->availableEvents;
	}

	/**
	 * Returns a single notification event
	 *
	 * @param string $id The return value of the event getId()
	 * @param mixed  $default
	 *
	 * @return SproutEmailBaseEvent
	 */
	public function getEventById($id, $default = null)
	{
		return isset($this->availableEvents[$id]) ? $this->availableEvents[$id] : $default;
	}

	public function getEventSelectedOptions($event, $notification)
	{
		if (craft()->request->getRequestType() == 'POST')
		{
			return $event->prepareOptions();
		}

		if ($notification)
		{
			return $event->prepareValue($notification->options);
		}
	}

	public function saveNotification(SproutEmail_NotificationEmailModel $notificationEmail, $isSettingPage = false)
	{
		$result = false;

		$isNewEntry              = true;
		$notificationEmailRecord = new SproutEmail_NotificationEmailRecord();

		if (!empty($notificationEmail->id))
		{
			$notificationEmailRecord = SproutEmail_NotificationEmailRecord::model()->findById($notificationEmail->id);

			$isNewEntry = false;

			if (!$notificationEmailRecord)
			{
				throw new Exception(Craft::t('No entry exists with the ID “{id}”', array('id' => $notificationEmail->id)));
			}
		}
		else
		{
			$notificationEmailRecord->subjectLine = $notificationEmail->subjectLine;
		}

		$eventId = $notificationEmail->eventId;
		$event   = $this->getEventById($eventId);

		if ($event && $isSettingPage == false)
		{
			$options = $event->prepareOptions();

			$notificationEmail->setAttribute('options', $options);
		}

		if (!empty($notificationEmail->getAttributes()))
		{
			foreach ($notificationEmail->getAttributes() as $handle => $value)
			{
				$notificationEmailRecord->setAttribute($handle, $value);
			}
		}

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

		if ($notificationEmailRecord->validate())
		{
			$fieldLayout = $notificationEmail->getFieldLayout();

			// Assign our new layout id info to our
			// form model and records
			$notificationEmail->fieldLayoutId       = $fieldLayout->id;
			$notificationEmailRecord->fieldLayoutId = $fieldLayout->id;

			try
			{
				if (craft()->elements->saveElement($notificationEmail))
				{
					if ($isNewEntry)
					{
						$notificationEmailRecord->id = $notificationEmail->id;
					}

					if ($notificationEmailRecord->save(false))
					{
						// Save recipient after record is saved to avoid Integrity constraint violation.
						//sproutEmail()->notificationEmails->saveRecipientLists($notification);

						$mailer = sproutEmail()->mailers->getMailerByName('defaultmailer');

						sproutEmail()->mailers->saveRecipientLists($mailer, $notificationEmail);

						if ($transaction && $transaction->active)
						{
							$transaction->commit();
						}

						$result = true;
					}
					else
					{
						$notificationEmail->addErrors($notificationEmailRecord->getErrors());
					}
				}
			}
			catch (\Exception $e)
			{
				if ($transaction && $transaction->active)
				{
					$transaction->rollback();
				}

				throw $e;
			}
		}
		else
		{
			$notificationEmail->addErrors($notificationEmailRecord->getErrors());
		}

		if (!$result)
		{
			if ($transaction && $transaction->active)
			{
				$transaction->rollback();
			}
		}

		return $result;
	}

	public function deleteNotificationEmailById($id)
	{
		$result = false;

		$result = craft()->elements->deleteElementById($id);

		return $result;
	}

	public function saveRecipientLists(SproutEmail_NotificationEmailModel $notificationEmail)
	{
		$this->deleteRecipientListsById($notificationEmail->id);

		$lists = $this->prepareRecipientLists($notificationEmail);

		if ($lists && is_array($lists) && count($lists))
		{
			foreach ($lists as $list)
			{
				$recipientListRelationsRecord = SproutEmail_RecipientListRelationsRecord::model()->findByAttributes(
					array(
						'emailId' => $notificationEmail->id,
						'list'    => $list->list
					)
				);

				$recipientListRelationsRecord = $recipientListRelationsRecord ? $recipientListRelationsRecord : new SproutEmail_RecipientListRelationsRecord();

				$recipientListRelationsRecord->emailId = $list->notificationId;
				$recipientListRelationsRecord->list    = $list->list;

				try
				{
					$recipientListRelationsRecord->save();
				}
				catch (\Exception $e)
				{
					throw $e;
				}
			}
		}

		return true;
	}

	public function deleteRecipientListsById($id)
	{
		if (($lists = SproutEmail_RecipientListRelationsRecord::model()->findAllByAttributes(array(
			'emailId' => $id
		)))
		)
		{
			foreach ($lists as $list)
			{
				$list->delete();
			}
		}
	}

	public function prepareRecipientLists(SproutEmail_NotificationEmailModel $notificationEmail)
	{
		$ids   = craft()->request->getPost('recipient.recipientLists');
		$lists = array();

		if ($ids)
		{
			foreach ($ids as $id)
			{
				$model = new SproutEmail_RecipientListRelationsModel();

				$model->setAttribute('emailId', $notificationEmail->id);
				$model->setAttribute('list', $id);

				$lists[] = $model;
			}
		}

		return $lists;
	}

	public function getRecipientListsByNotificationId($id)
	{
		if (($lists = SproutEmail_RecipientListRelationsRecord::model()->findAllByAttributes(array(
			'emailId' => $id
		)))
		)
		{
			return SproutEmail_RecipientListRelationsModel::populateModels($lists);
		}
	}

	/**
	 * Registers a closure for each event we should be listening for via craft()->on()
	 *
	 * @note
	 * 1. Get all events that we need to listen for
	 * 2. Register an anonymous function for each event using craft()->on()
	 * 3. That function will be called with an event id and the event itself when triggered
	 *
	 * @return mixed
	 */
	public function registerDynamicEventHandler()
	{
		$self   = $this;
		$events = $this->getAvailableEvents();

		if (count($events))
		{
			foreach ($events as $eventId => $listener)
			{
				if ($listener instanceof SproutEmailBaseEvent)
				{
					$self->registerEvent($eventId, $self->getDynamicEventHandler());

					if ($listener->getName() == 'userSession.login' && craft()->isConsole() == true)
					{
						continue;
					}

					craft()->on($listener->getName(), function (Event $event) use ($self, $eventId, $listener)
					{
						return call_user_func_array(
							$self->getRegisteredEvent($eventId), array(
								$eventId,
								$event,
								$listener
							)
						);
					});
				}
			}
		}
	}

	/**
	 * Registers an event listener to be trigger dynamically
	 *
	 * @param string    $eventId
	 * @param \Callable $callback
	 */
	public function registerEvent($eventId, $callback)
	{
		$this->registeredEvents[$eventId] = $callback;
	}

	/**
	 * Returns a callable for the given event
	 *
	 * @param string $eventId
	 *
	 * @return \Callable
	 */
	public function getRegisteredEvent($eventId)
	{
		if (isset($this->registeredEvents[$eventId]))
		{
			return $this->registeredEvents[$eventId];
		};

		return function ()
		{
		};
	}

	/**
	 * Returns the callable/closure that handle dynamic event delegation for craft()->raiseEvent()
	 *
	 * This closure is necessary to avoid creating a method for every possible event (entries.saveEntry)
	 * This closure allows us to avoid having to register for every possible event via craft()->on()
	 * This closure allows us to know the current event being triggered dynamically
	 *
	 * @example
	 * When the sproutemail is initialized...
	 * 1. We check what events we need to register for via craft()->on()
	 * 2. We register an anonymous function as the handler
	 * 3. This closure gets called with the name of the event and the event itself
	 * 4. This closure executes as real event handler for the triggered event
	 *
	 * @return \Callable
	 */
	public function getDynamicEventHandler()
	{
		$self = $this;

		return function ($eventId, Event $event, SproutEmailBaseEvent $listener) use ($self)
		{
			return $self->handleDynamicEvent($eventId, $event, $listener);
		};
	}

	/**
	 * @param string               $eventId
	 * @param Event                $event
	 * @param SproutEmailBaseEvent $listener
	 *
	 * @return bool
	 */
	public function handleDynamicEvent($eventId, Event $event, SproutEmailBaseEvent $listener)
	{
		$params  = $listener->prepareParams($event);
		$element = isset($params['value']) ? $params['value'] : null;

		if ($notificationEmails = $this->getNotifications($eventId))
		{
			foreach ($notificationEmails as $notificationEmail)
			{
				if ($listener->validateOptions($notificationEmail['options'], $element, $params))
				{
					$notificationEmail = craft()->elements->getElementById($notificationEmail->id);

					$this->relayNotificationThroughAssignedMailer($notificationEmail, $element);
				}
			}
		}
	}

	/**
	 * Returns an array of recipients from the event element if any are found
	 * This allows us to allow notifications to have recipients defined at runtime
	 *
	 * @param mixed $element Most likely a BaseElementModel but can be an array of event a string
	 *
	 * @return array
	 */
	public function getDynamicRecipientsFromElement($element)
	{
		$recipients = array();

		if (is_object($element) && $element instanceof BaseElementModel)
		{
			if (isset($element->sproutEmailRecipients) && is_array($element['sproutEmailRecipients']) && count($element['sproutEmailRecipients']))
			{
				$recipients = (array) $element->sproutEmailRecipients;
			}
		}

		if (is_array($element) && isset($element['sproutEmailRecipients']))
		{
			if (is_array($element['sproutEmailRecipients']) && count($element['sproutEmailRecipients']))
			{
				$recipients = $element['sproutEmailRecipients'];
			}
		}

		return $recipients;
	}

	/**
	 * Returns an array or variables for a notification template
	 *
	 * @example
	 * The following syntax is supported to access event element properties
	 *
	 * {attribute}
	 * {{ attribute }}
	 * {{ object.attribute }}
	 *
	 * @param BaseModel  $notificationEmail
	 * @param mixed|null $element
	 *
	 * @return array
	 */
	public function prepareNotificationTemplateVariables(BaseModel $notificationEmail, $element = null)
	{
		if (is_object($element) && method_exists($element, 'getAttributes'))
		{
			$attributes = $element->getAttributes();

			if (isset($element->elementType))
			{
				$content = $element->getContent()->getAttributes();

				if (count($content))
				{
					foreach ($content as $key => $value)
					{
						if (!isset($attributes[$key]))
						{
							$attributes[$key] = $value;
						}
					}
				}
			}
		}
		else
		{
			$attributes = (array) $element;
		}

		$vars = array_merge(
			$attributes,
			array(
				'email'        => $notificationEmail,
				'object'       => $element,

				// @deprecate - in v3 in favor of `email`
				'entry'        => $notificationEmail,
				'notification' => $notificationEmail,
			)
		);

		return $vars;
	}

	/**
	 * @param SproutEmail_NotificationEmailModel $notificationEmail
	 * @param mixed                              $object Will be an element model most of the time
	 *
	 * @return mixed
	 * @throws Exception
	 * @throws \Exception
	 */
	protected function relayNotificationThroughAssignedMailer(SproutEmail_NotificationEmailModel $notificationEmail, $object)
	{
		$mailer = sproutEmail()->mailers->getMailerByName("defaultmailer");

		if (!method_exists($mailer, 'sendNotification'))
		{
			throw new Exception(Craft::t('The {mailer} does not have a sendNotification() method.', array('mailer' => get_class($mailer))));
		}

		try
		{
			return $mailer->sendNotification($notificationEmail, $object);
		}
		catch (\Exception $e)
		{
			throw $e;
		}
	}

	public function getPrepareModal($notificationId)
	{
		$notificationEmail = craft()->elements->getElementById($notificationId);

		$response = new SproutEmail_ResponseModel();

		if ($notificationEmail)
		{
			try
			{
				$response->success = true;
				$response->content = $this->getPrepareModalHtml($notificationEmail);

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
			$response->success = false;
			$response->message = "<p>" . Craft::t('No actions available for this notification.') . "</p>";
		}

		return $response;
	}

	public function getPrepareModalHtml(SproutEmail_NotificationEmailModel $notificationEmail)
	{
		// Display the testToEmailAddress if it exists
		$email = craft()->config->get('testToEmailAddress');

		if (empty($email))
		{
			$email = craft()->userSession->getUser()->email;
		}

		$errors = array();

		$errors = $this->getErrors($notificationEmail, $errors);

		return craft()->templates->render(
			'sproutemail/notifications/_modals/prepare',
			array(
				'notification' => $notificationEmail,
				'recipient'    => $email,
				'errors'       => $errors
			)
		);
	}

	public function getErrors($notificationEmail, $errors)
	{
		$notificationEditUrl         = UrlHelper::getCpUrl('sproutemail/notifications/edit/' . $notificationEmail->id);
		$notificationEditSettingsUrl = UrlHelper::getCpUrl('sproutemail/notifications/setting/' . $notificationEmail->id);

		$event = $this->getEventById($notificationEmail->eventId);

		if ($event)
		{
			$object = $event->getMockedParams();

			$template = $notificationEmail->template;

			$emailModel = new EmailModel();

			sproutEmail()->defaultmailer->renderEmailTemplates($emailModel, $template, $notificationEmail, $object);

			$templateErrors = sproutEmail()->getError();

			if (!empty($templateErrors))
			{
				foreach ($templateErrors as $templateError)
				{
					$errors[] = Craft::t($templateError . ' <a href="{url}">Edit Settings</a>.',
						array(
							'url' => $notificationEditSettingsUrl
						));
				}
			}
		}
		else
		{
			$errors[] = Craft::t('No Event is selected. <a href="{url}">Edit Notification</a>.', array(
				'url' => $notificationEditUrl
			));
		}

		return $errors;
	}

	public function exportEmail(SproutEmail_NotificationEmailModel $notificationEmail)
	{
		$lists          = $this->getRecipientListsByNotificationId($notificationEmail->id);
		$recipientLists = array();

		if (count($lists))
		{
			foreach ($lists as $list)
			{
				$recipientList = sproutEmailDefaultMailer()->getRecipientListById($list->list);

				if ($recipientList)
				{
					$recipientLists[] = $recipientList;
				}
			}
		}

		try
		{
			$response = $this->sendMockNotification($notificationEmail);

			return SproutEmail_ResponseModel::createModalResponse(
				'sproutemail/notifications/_modals/export',
				array(
					'notification'  => $notificationEmail,
					'emailModel'    => $response['emailModel'],
					'recipentLists' => $recipientLists,
					'message'       => Craft::t('Notification sent successfully.')
				)
			);
		}
		catch (\Exception $e)
		{
			sproutEmail()->error($e->getMessage());

			return SproutEmail_ResponseModel::createErrorModalResponse(
				'sproutemail/notifications/_modals/export',
				array(
					'notification' => $notificationEmail,
					'message'      => Craft::t($e->getMessage()),
				)
			);
		}
	}

	public function sendMockNotification(SproutEmail_NotificationEmailModel $notification)
	{
		$event = $this->getEventById($notification->eventId);

		if ($event)
		{
			try
			{
				$mailer = sproutEmail()->mailers->getMailerByName("defaultmailer");

				$sent = $mailer->sendNotification($notification, $event->getMockedParams(), true);

				if (!$sent)
				{
					$customErrorMessage = sproutEmail()->getError();

					if (!empty($customErrorMessage))
					{
						$message = $customErrorMessage;
					}
					else
					{
						$message = Craft::t('Unable to send mock notification. Check email settings');
					}
					throw new \Exception($message);
				}
			}
			catch (\Exception $e)
			{
				throw $e;
			}
		}

		return false;
	}

	/**
	 * Retrieves a rendered Notification Email to be shared or for Live Preview
	 *
	 * @param      $notificationId
	 * @param null $type
	 */
	public function getPreviewNotificationEmailById($notificationId, $type = null)
	{
		$notificationEmail = sproutEmail()->notificationEmails->getNotificationEmailById($notificationId);

		$eventId = $notificationEmail->eventId;

		$event = sproutEmail()->notificationEmails->getEventById($eventId);

		if ($event)
		{
			$object = $event->getMockedParams();
		}

		$email         = new EmailModel();
		$template      = $notificationEmail->template;
		$fileExtension = ($type != null && $type == 'text') ? 'txt' : 'html';

		$email = sproutEmail()->defaultmailer->renderEmailTemplates($email, $template, $notificationEmail, $object);

		sproutEmail()->campaignEmails->showCampaignEmail($email, $fileExtension);
	}
}
