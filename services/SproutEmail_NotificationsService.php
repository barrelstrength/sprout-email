<?php
namespace Craft;

class SproutEmail_NotificationsService extends BaseApplicationComponent
{
	protected $availableEvents;
	protected $registeredEvents;
	protected $dynamicallyRegisteredEvents = array();
	protected $notificationsWithCampaignsAndRecipients = array();

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
							$this->availableEvents[$event->getId()] = $event; // entries-saveEntry
						}
					}
				}
			}

			if (count($this->availableEvents))
			{
				asort($this->availableEvents);
			}
		}

		return $this->availableEvents;
	}

	/**
	 * Returns single notification event
	 *
	 * @param string $id The return value of the event getId()
	 * @param mixed  $default
	 *
	 * @return array
	 */
	public function getEventById($id, $default = null)
	{
		return isset($this->availableEvents[$id]) ? $this->availableEvents[$id] : $default;
	}

	/**
	 * Save a notification event to the database
	 *
	 * @param int                  $campaignId
	 * @param SproutEmailBaseEvent $event
	 *
	 * @return bool
	 */
	public function save($event, $campaignId)
	{
		$attributes   = array('eventId' => $event->getId(), 'campaignId' => $campaignId);
		$notification = SproutEmail_NotificationRecord::model()->findByAttributes($attributes);

		if (!$notification)
		{
			$notification = new SproutEmail_NotificationRecord;
		}

		$notification->setAttribute('eventId', $event->getId());
		$notification->setAttribute('campaignId', $campaignId);

		if ($event)
		{
			$options = $event->prepareOptions();

			$notification->setAttribute('options', $options);
		}

		return $notification->save(false);
	}

	/**
	 * Returns all campaign notifications based on the passed event
	 *
	 * @param string    $eventId
	 * @param BaseModel $element
	 *
	 * @return array
	 */
	public function getNotifications($eventId, $element = null)
	{
		$attributes = array('eventId' => $eventId);

		return SproutEmail_NotificationRecord::model()->findAllByAttributes($attributes);
	}

	/**
	 * @param array $attributes
	 *
	 * @return SproutEmail_NotificationRecord
	 */
	public function getNotification(array $attributes)
	{
		return SproutEmail_NotificationRecord::model()->findByAttributes($attributes);
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
		$events = sproutEmail()->notifications->getAvailableEvents();

		if (count($events))
		{
			foreach ($events as $eventId => $listener)
			{
				if ($listener instanceof SproutEmailBaseEvent)
				{
					$this->dynamicallyRegisteredEvents[$eventId] = $this->getDynamicEventHandler();

					craft()->on(
						$listener->getName(), function (Event $event) use ($self, $eventId, $listener)
						{
							return call_user_func_array(
								$self->dynamicallyRegisteredEvents[$eventId], array(
									$eventId,
									$event,
									$listener
								)
							);
						}
					);
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
	 * @return closure
	 */
	public function getDynamicEventHandler()
	{
		return function ($eventId, Event $event, SproutEmailBaseEvent $listener)
		{
			return $this->handleDynamicEvent($eventId, $event, $listener);
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
		$notifications  = $this->getNotificationsWithCampaignsAndRecipients($eventId);
		$preparedParams = $listener->prepareParams($event);
		$model          = isset($preparedParams['value']) ? $preparedParams['value'] : null;

		if ($notifications)
		{
			foreach ($notifications as $categoryId => $notification)
			{
				if (!$notification['recipients'])
				{
					continue;
				}

				if ($listener->validateOptions($notification['options'], $model))
				{
					return $this->relayNotificationThroughEmailProvider($notification, $model);
				}
			}
		}
	}

	protected function getNotificationsWithCampaignsAndRecipients($eventId)
	{
		if (!isset($this->notificationsWithCampaignsAndRecipients[$eventId]))
		{
			$prefix = craft()->config->get('tablePrefix', ConfigFile::Db);

			$query = " SELECT"
				." n.eventId, n.campaignId, n.options,"
				." c.name, c.handle, c.mailer, c.type, c.titleFormat, c.template"
				." FROM {$prefix}_sproutemail_campaigns_notifications n"
				." JOIN {$prefix}_sproutemail_campaigns c ON c.id = n.campaignId"
				." WHERE n.eventId = '{$eventId}'";

			$results = craft()->db->createCommand($query)->queryAll();

			if ($results)
			{
				foreach ($results as $result)
				{
					$result['options']    = json_decode($result['options']);
					$result['recipients'] = json_decode($result['recipients']);

					$this->notificationsWithCampaignsAndRecipients[$eventId][$result['campaignId']] = $result;
				}
			}
		}

		return isset($this->notificationsWithCampaignsAndRecipients[$eventId]) ? $this->notificationsWithCampaignsAndRecipients[$eventId] : array();
	}

	/**
	 * @param array     $notification
	 * @param BaseModel $model
	 *
	 * @throws Exception
	 * @throws \Exception
	 * @return mixed
	 */
	protected function relayNotificationThroughEmailProvider($notification, BaseModel $model)
	{
		if (!isset($notification['emailProvider']))
		{
			throw new Exception('An email provider is required to send the notification.');
		}

		$provider = 'sproutEmail_'.lcfirst($notification['emailProvider']);

		if (!craft()->hasComponent($provider))
		{
			throw new Exception(Craft::t('The email provider {p} is not available.', array('p' => $provider)));
		}

		$provider = craft()->getComponent($provider);

		if (!method_exists($provider, 'sendNotification'))
		{
			throw new Exception(Craft::t('The {p} does not have a sendNotification() method.', array('p' => get_class($provider))));
		}

		try
		{
			return $provider->sendNotification($notification, $model);
		}
		catch (\Exception $e)
		{
			throw $e;
		}
	}
}
