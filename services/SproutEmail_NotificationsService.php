<?php
namespace Craft;

class SproutEmail_NotificationsService extends BaseApplicationComponent
{
	protected $availableEvents;
	protected $registeredEvents;
	protected $dynamicallyRegisteredEvents = array();
	protected $notificationsWithCampaignAndRecipients = array();

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
	 * @param SproutEmailBaseEvent|string $eventId
	 *
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
		$params  = $listener->prepareParams($event);
		$element = isset($params['value']) ? $params['value'] : null;

		if (($notifications = $this->getNotifications($eventId)))
		{
			foreach ($notifications as $notification)
			{
				if ($listener->validateOptions($notification['options'], $element))
				{
					$campaign = sproutEmail()->campaigns->getCampaignById($notification->campaignId);

					return $this->relayNotificationThroughAssignedMailer($campaign, $element);
				}
			}
		}
	}

	/**
	 * @param SproutEmail_CampaignModel $campaign
	 * @param BaseElementModel          $element
	 *
	 * @throws Exception
	 * @throws \Exception
	 * @return mixed
	 */
	protected function relayNotificationThroughAssignedMailer(SproutEmail_CampaignModel $campaign,BaseElementModel $element)
	{
		if (!isset($campaign->mailer))
		{
			throw new Exception('An email provider is required to send the notification.');
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
			return $mailer->sendNotification($campaign, $element);
		}
		catch (\Exception $e)
		{
			throw $e;
		}
	}
}
