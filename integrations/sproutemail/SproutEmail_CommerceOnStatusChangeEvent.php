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
		return Craft::t('When an order status is changed.');
	}

	/**
	 * Returns a short description of this event
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return Craft::t('Triggers when an order status is changed.');
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
		return array(
			'value' 	   => $event->params['order'],
			'orderHistory' => $event->params['orderHistory']
		);
	}

	/**
	 * Returns the value that should be saved to options for the notification (registered event)
	 *
	 * @return mixed
	 */
	public function prepareOptions()
	{
		return array(
			'commerceOrderStatuses' =>  craft()->request->getPost('commerceOrderStatuses')
		);
	}

	public function getOptionsHtml($context = array())
	{
		$context['statuses'] = $this->getAllOrderStatuses();

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
	public function validateOptions($options, Commerce_OrderModel  $order, array $params = array())
	{
		// This will ensure that order updated at the backend only get triggered.
		$prevStatusId = $params['orderHistory']->prevStatusId;
		if($prevStatusId != null)
		{
			if(!empty($options['commerceOrderStatuses']))
			{
				// Get first transaction which is the current transaction
				if (!in_array($order->orderStatusId, $options['commerceOrderStatuses']))
				{
					return false;
				}
			}

			return true;
		}
		else
		{
			return false;
		}
	}

	public function getAllOrderStatuses()
	{
		$statuses = craft()->commerce_orderStatuses->getAllOrderStatuses();
		$options = array();
		if(!empty($statuses))
		{
			foreach($statuses as $status)
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
}