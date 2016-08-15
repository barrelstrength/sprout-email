<?php
namespace Craft;

class SproutEmail_CommerceOnStatusChangeEvent extends SproutEmailBaseEvent
{
	/**
	 * Returns the qualified event name to use when registering with craft()->on
	 *
	 * @return string
	 */
	public function getName()
	{
		return 'commerce_orderHistories.onStatusChange';
	}

	/**
	 * Returns the event title to use when displaying a label or similar use case
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return Craft::t('When an Craft Commerce order status is changed');
	}

	/**
	 * Returns a short description of this event
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return Craft::t('Triggers when an order status is changed');
	}

	/**
	 * Returns the data passed in by the triggered event
	 *
	 * @param Event $event
	 *
	 * @return mixed
	 */
	public function prepareParams(Event $event)
	{
		$values                          = array();
		$values['value']['order']        = $event->params['order'];
		$values['value']['orderHistory'] = $event->params['orderHistory'];

		return $values;
	}

	/**
	 * Returns the value that should be saved to options for the notification (registered event)
	 *
	 * @return mixed
	 */
	public function prepareOptions()
	{
		return array(
			'commerceOrderStatuses' => craft()->request->getPost('commerceOrderStatuses')
		);
	}

	public function getOptionsHtml($context = array())
	{
		$context['statuses'] = $this->getAllOrderStatuses();

		$context['oldFieldValue'] = '';
		$context['newFieldValue'] = '';

		if (isset($context['options']['commerceOrderStatuses']))
		{
			$oldOptions               = $context['options']['commerceOrderStatuses']['old'];
			$context['oldFieldValue'] = sproutEmail()->mailers->getCheckboxFieldValue($oldOptions);

			$newOptions               = $context['options']['commerceOrderStatuses']['new'];
			$context['newFieldValue'] = sproutEmail()->mailers->getCheckboxFieldValue($newOptions);
		}

		return craft()->templates->render('sproutemail/_events/statusChange', $context);
	}

	/**
	 * Returns whether or not the entry meets the criteria necessary to trigger the event
	 *
	 * @param mixed      $options
	 * @param EntryModel $entry
	 * @param array      $params
	 *
	 * @return bool
	 */
	public function validateOptions($options, $order, array $params = array())
	{
		// This ensures that we will only trigger orders being updated
		$prevStatusId = $order['orderHistory']->prevStatusId;

		if (!$prevStatusId OR empty($options['commerceOrderStatuses']))
		{
			return false;
		}

		$newStatusId = $order['order']->orderStatusId;

		$isMatch = $this->isOldAndNewMatch($prevStatusId, $newStatusId, $options);

		return $isMatch;
	}

	public function getAllOrderStatuses()
	{
		$statuses = craft()->commerce_orderStatuses->getAllOrderStatuses();
		$options  = array();
		if (!empty($statuses))
		{
			foreach ($statuses as $status)
			{
				array_push(
					$options, array(
						'label' => $status->name,
						'value' => $status->id
					)
				);
			}
		}

		return $options;
	}

	private function isOldAndNewMatch($oldId, $newId, $options)
	{
		if (($this->isSettingsMatch($oldId, 'old', $options) OR $options['commerceOrderStatuses']['old'] == "*")
			AND
			($this->isSettingsMatch($newId, 'new', $options) OR $options['commerceOrderStatuses']['new'] == "*")
		)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	private function isSettingsMatch($id, $key = 'old', $options)
	{
		if (is_array($options['commerceOrderStatuses'][$key])
			AND
			in_array($id, $options['commerceOrderStatuses'][$key])
		)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * @throws Exception
	 *
	 * @return BaseElementModel|null
	 */
	public function getMockedParams()
	{
		$values = array();

		if ($order = craft()->sproutEmail_craftCommerce->getRecentOrder())
		{
			$values['order'] = $order;

			$orderHistories = craft()->commerce_orderHistories->getAllOrderHistoriesByOrderId($order->id);

			if (count($orderHistories))
			{
				$values['orderHistory'] = end($orderHistories);
			}
		}

		return $values;
	}
}