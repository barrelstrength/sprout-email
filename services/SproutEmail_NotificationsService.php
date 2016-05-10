<?php
namespace Craft;

/**
 * The Notifications API service layer
 *
 * Class SproutEmail_NotificationsService
 *
 * @package Craft
 */
class SproutEmail_NotificationsService extends BaseApplicationComponent
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

		if (($notifications = SproutEmail_NotificationRecord::model()->findAllByAttributes($attributes)))
		{
			return SproutEmail_NotificationModel::populateModels($notifications);
		}
	}

	/**
	 * Save a notification event to the database
	 *
	 * @param SproutEmailBaseEvent|string $eventId
	 * @param int                         $campaignId
	 *
	 * @throws Exception
	 * @return bool
	 */
	public function save($eventId, $campaignId)
	{
		$event = $this->getEventById($eventId);

		if (!$event)
		{
			throw new Exception(Craft::t('The event with id ({id}) does not exist.', array('id' => $eventId)));
		}

		$attributes = array('campaignId' => $campaignId);
		$notification = SproutEmail_NotificationRecord::model()->findByAttributes($attributes);

		if (!$notification)
		{
			$notification = new SproutEmail_NotificationRecord;
		}

		$notification->setAttribute('eventId', $event->getEventId());
		$notification->setAttribute('campaignId', $campaignId);

		if ($event)
		{
			$options = $event->prepareOptions();

			$notification->setAttribute('options', $options);
		}

		return $notification->save(false);
	}

	public function deleteNotificationsByCampaignId($campaignId)
	{
		$return = craft()->db->createCommand()->delete('sproutemail_campaigns_notifications',
			'campaignId = :campaignId', array(':campaignId' => $campaignId)
		);

		return $return;
	}

	/**
	 * @param array $attributes
	 *
	 * @return SproutEmail_NotificationRecord
	 */
	public function getNotification(array $attributes)
	{
		$record = SproutEmail_NotificationRecord::model()->findByAttributes($attributes);

		if ($record)
		{
			return SproutEmail_NotificationModel::populateModel($record->getAttributes());
		}
	}

	/**
	 * @param id
	 *
	 * @return SproutEmail_NotificationRecord
	 */
	public function getNotificationById($id)
	{
		return $this->getNotification(array('id' => $id));
	}

	/**
	 * @param $id
	 *
	 * @return SproutEmailBaseEvent|null
	 */
	public function getEventByCampaignId($id)
	{
		$notification = $this->getNotification(array('campaignId' => $id));

		if ($notification)
		{
			$event = $this->getEventById($notification->eventId);

			if ($event)
			{
				$event->setOptions($notification->options);

				return $event;
			}
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
		$events = sproutEmail()->notifications->getAvailableEvents();

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

		if (($notifications = $this->getNotifications($eventId)))
		{
			foreach ($notifications as $notification)
			{
				if ($listener->validateOptions($notification['options'], $element, $params))
				{
					$campaign = sproutEmail()->campaigns->getCampaignById($notification->campaignId);

					$this->relayNotificationThroughAssignedMailer($campaign, $element);
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
	 * @param SproutEmail_CampaignModel $campaign
	 * @param mixed                     $object Will be an element model most of the time
	 *
	 * @return mixed
	 * @throws Exception
	 * @throws \Exception
	 */
	protected function relayNotificationThroughAssignedMailer(SproutEmail_CampaignModel $campaign, $object)
	{
		if (!isset($campaign->mailer))
		{
			throw new Exception(Craft::t('An email provider is required to send the notification.'));
		}

		$mailer = sproutEmail()->mailers->getMailerByName($campaign->mailer);

		if (!$mailer)
		{
			throw new Exception(Craft::t('The email provider {mailer} is not available.', array('mailer' => $campaign->mailer)));
		}

		if (!method_exists($mailer, 'sendNotification'))
		{
			throw new Exception(Craft::t('The {mailer} does not have a sendNotification() method.', array('mailer' => get_class($mailer))));
		}

		try
		{
			return $mailer->sendNotification($campaign, $object);
		}
		catch (\Exception $e)
		{
			throw $e;
		}
	}

	public function getNotificationEntryByCampaignId($id)
	{
		$record = SproutEmail_EntryRecord::model()->findByAttributes(array('campaignId' => $id));

		if ($record)
		{
			return SproutEmail_EntryModel::populateModel($record);
		}

		return false;
	}
}
