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
}
