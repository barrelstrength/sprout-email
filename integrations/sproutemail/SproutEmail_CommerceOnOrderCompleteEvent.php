<?php
namespace Craft;

class SproutEmail_CommerceOnOrderCompleteEvent extends SproutEmailBaseEvent
{
	/**
	 * Returns the qualified event name to use when registering with craft()->on
	 *
	 * @return string
	 */
	public function getName()
	{
		return 'commerce_orders.onOrderComplete';
	}

	/**
	 * Returns the event title to use when displaying a label or similar use case
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return Craft::t('When an Craft Commerce order is completed');
	}

	/**
	 * Returns a short description of this event
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return Craft::t('Triggers when an order is completed');
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
			'value' => $event->params['order']
		);
	}

	/**
	 * Returns the value that should be saved to options for the notification (registered event)
	 *
	 * @return mixed
	 */
	public function prepareOptions()
	{
		return array();
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
	public function validateOptions($options, Commerce_OrderModel $order, array $params = array())
	{
		return true;
	}

	/**
	 * @throws Exception
	 *
	 * @return BaseElementModel|null
	 */
	public function getMockedParams()
	{
		return craft()->sproutEmail_craftCommerce->getRecentOrder();
	}
}