<?php
namespace Craft;

/**
 * The NotificationEmail API service layer
 *
 * Class SproutEmail_NotificationsService
 *
 * @package Craft
 */
class SproutEmail_NotificationEmailService extends BaseApplicationComponent
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

	public function getNotificationByVariables($variables, $session = null, $elementService = null)
	{
		$notification = null;

		if (isset($variables['notification']))
		{
			$notification = $variables['notification'];
		}
		elseif (isset($variables['notificationId']))
		{
			$notificationId = $variables['notificationId'];

			if ($elementService == null)
			{
				$elementService = craft()->elements;
			}
			$notification = $elementService->getElementById($notificationId);
		}
		elseif ($session != null)
		{
			$notification = unserialize($session);
		}
		else
		{
			$notification = new SproutEmail_NotificationEmailModel();
		}

		return $notification;
	}

	public function saveNotification(SproutEmail_NotificationEmailModel $notification)
	{
		$result = false;

		$isNewEntry  = true;
		$record = new SproutEmail_NotificationEmailRecord();

		if (!empty($notification->id))
		{
			$record = SproutEmail_NotificationEmailRecord::model()->findById($notification->id);

			$isNewEntry  = false;
			if (!$record)
			{
				throw new Exception(Craft::t('No entry exists with the ID “{id}”', array('id' => $notification->id)));
			}
		}
		else
		{
			$record->subjectLine = $notification->subjectLine;
		}

		$eventId = $notification->eventId;
		$event   = $this->getEventById($eventId);

		if ($event)
		{
			$options = $event->prepareOptions();

			$notification->setAttribute('options', $options);
		}


		if (!empty($notification->getAttributes()))
		{
			foreach ($notification->getAttributes() as $handle => $value)
			{
				$record->setAttribute($handle, $value);
			}
		}

		if ($record->validate())
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

			$fieldLayout = $notification->getFieldLayout();

			// Assign our new layout id info to our
			// form model and records
			$notification->fieldLayoutId = $fieldLayout->id;
			$record->fieldLayoutId       = $fieldLayout->id;


			try
			{
				if (craft()->elements->saveElement($notification))
				{
					if ($isNewEntry)
					{
						$record->id = $notification->id;
					}

					if($record->save(false))
					{
						// Save recipient after record is saved to avoid Integrity constraint violation.
						sproutEmail()->notificationemail->saveRecipientLists($notification);

						if ($transaction && $transaction->active)
						{
							$transaction->commit();
						}

						$result = true;
					}
					else
					{
						$notification->addErrors($record->getErrors());
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
			$notification->addErrors($record->getErrors());
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

	public function deleteNotificationById($id)
	{
		$result = false;

		$result = craft()->elements->deleteElementById($id);

		return $result;
	}

	public function saveRecipientLists(SproutEmail_NotificationEmailModel $notification)
	{
		$this->deleteRecipientListsById($notification->id);

		$lists = $this->prepareRecipientLists($notification);

		if ($lists && is_array($lists) && count($lists))
		{
			foreach ($lists as $list)
			{
				$record = SproutEmail_NotificationRecipientListRecord::model()->findByAttributes(
					array(
						'notificationId' => $notification->id,
						'list'    => $list->list
					)
				);

				$record = $record ? $record : new SproutEmail_NotificationRecipientListRecord();

				$record->notificationId = $list->notificationId;
				$record->list           = $list->list;

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

	public function deleteRecipientListsById($id)
	{
		if (($lists = SproutEmail_NotificationRecipientListRecord::model()->findAllByAttributes(array('notificationId' =>
			                                                                                              $id))))
		{
			foreach ($lists as $list)
			{
				$list->delete();
			}
		}
	}

	public function prepareRecipientLists(SproutEmail_NotificationEmailModel $notification)
	{
		$ids   = craft()->request->getPost('recipient.recipientLists');
		$lists = array();

		if ($ids)
		{
			foreach ($ids as $id)
			{
				$model = new SproutEmail_NotificationRecipientListModel();

				$model->setAttribute('notificationId', $notification->id);
				$model->setAttribute('list', $id);

				$lists[] = $model;
			}
		}

		return $lists;
	}

	public function getRecipientListsByNotificationId($id)
	{
		if (($lists = SproutEmail_NotificationRecipientListRecord::model()->findAllByAttributes(array('notificationId' =>
			                                                                                              $id))))		{
			return SproutEmail_NotificationRecipientListModel::populateModels($lists);
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
		$self = $this;
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
		$params = $listener->prepareParams($event);
		$element = isset($params['value']) ? $params['value'] : null;

		if ($notifications = $this->getNotifications($eventId))
		{
			foreach ($notifications as $notification)
			{
				if ($listener->validateOptions($notification['options'], $element, $params))
				{
					$notification = craft()->elements->getElementById($notification->id);

					$this->relayNotificationThroughAssignedMailer($notification, $element);
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
	 * @param BaseModel  $entry
	 * @param mixed|null $element
	 *
	 * @return array
	 */
	public function prepareNotificationTemplateVariables(BaseModel $entry, $element = null)
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
				'email'        => $entry,
				'object'       => $element,

				// @deprecate - in v3 in favor of `email`
				'entry'        => $entry,
				'notification' => $entry,
			)
		);

		return $vars;
	}

	/**
	 * @param SproutEmail_NotificationEmailModel $notification
	 * @param mixed  $object Will be an element model most of the time
	 *
	 * @return mixed
	 * @throws Exception
	 * @throws \Exception
	 */
	protected function relayNotificationThroughAssignedMailer(SproutEmail_NotificationEmailModel $notification, $object)
	{
		$mailer = sproutEmail()->mailers->getMailerByName("defaultmailer");

		if (!method_exists($mailer, 'sendNotification'))
		{
			throw new Exception(Craft::t('The {mailer} does not have a sendNotification() method.', array('mailer' => get_class($mailer))));
		}

		try
		{
			return $mailer->sendNotification($notification, $object);
		}
		catch (\Exception $e)
		{
			throw $e;
		}
	}

	/**
	 * Returns all campaign notifications based on the passed in event id
	 *
	 * @param string $eventId
	 *
	 * @return SproutEmail_NotificationModel[]|null
	 */
	public function getNotifications($eventId)
	{
		$attributes = array('eventId' => $eventId);

		if (($notifications = SproutEmail_NotificationEmailRecord::model()->findAllByAttributes($attributes)))
		{
			return SproutEmail_NotificationEmailModel::populateModels($notifications);
		}
	}

	public function getPrepareModal($notificationId)
	{
		$notification = craft()->elements->getElementById($notificationId);

		$response = new SproutEmail_ResponseModel();

		if ($notification)
		{
			try
			{
				$response->success = true;
				$response->content = $this->getPrepareModalHtml($notification);

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

	public function getPrepareModalHtml(SproutEmail_NotificationEmailModel $notification)
	{
		// Display the testToEmailAddress if it exists
		$email = craft()->config->get('testToEmailAddress');

		if (empty($email))
		{
			$email = craft()->userSession->getUser()->email;
		}

		$errors = array();

		$errors = $this->getErrors($notification, $errors);

		return craft()->templates->render(
			'sproutemail/notifications/_modals/prepare',
			array(
				'notification' => $notification,
				'recipient'    => $email,
				'errors'       => $errors
			)
		);
	}

	public function getErrors($notification, $errors)
	{
		$notificationEditUrl         = UrlHelper::getCpUrl('sproutemail/notifications/edit/' . $notification->id);
		$notificationEditSettingsUrl = UrlHelper::getCpUrl('sproutemail/notifications/setting/' . $notification->id);

		if (empty($notification->template))
		{
			$errors[] = Craft::t('Email Template setting is blank. <a href="{url}">Edit Settings</a>.', array(
				'url' => $notificationEditSettingsUrl
			));
		}

		$event = $this->getEventById($notification->eventId);

		if ($event)
		{
			$object = $event->getMockedParams();

			$vars = sproutEmail()->notifications->prepareNotificationTemplateVariables($notification, $object);

			// @todo - check for text template too
			$template = sproutEmail()->renderSiteTemplateIfExists($notification->template, $vars);

			if (empty($template))
			{
				$errors[] = Craft::t('{message} <a href="{url}">Edit Settings</a>', array(
					'message' => sproutEmail()->getError('template'),
					'url' => $notificationEditSettingsUrl
				));
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

	public function exportEntry(SproutEmail_NotificationEmailModel $notification)
	{
		$lists          = $this->getRecipientListsByNotificationId($notification->id);
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
			$response = $this->sendMockNotification($notification);

			return SproutEmail_ResponseModel::createModalResponse(
				'sproutemail/notifications/_modals/export',
				array(
					'notification' => $notification,
					'emailModel'   => $response['emailModel'],
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
					'notification' => $notification,
					'message'  => Craft::t($e->getMessage()),
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
					throw new Exception($message);
				}
			}
			catch (\Exception $e)
			{
				throw $e;
			}
		}

		return false;
	}
}
