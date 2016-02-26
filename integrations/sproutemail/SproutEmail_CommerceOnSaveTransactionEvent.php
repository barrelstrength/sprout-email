<?php
namespace Craft;

class SproutEmail_CommerceOnSaveTransactionEvent extends SproutEmailBaseEvent
{
	/**
	 * Returns the qualified event name to use when registering with craft()->on
	 * 
	 * @return string
	 */
	public function getName()
	{
		return 'commerce_transactions.onSaveTransaction';
	}

	/**
	 * Returns the event title to use when displaying a label or similar use case
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return Craft::t('When a Craft Commerce transaction is saved');
	}

	/**
	 * Returns a short description of this event
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return Craft::t('Triggers when payment is made on the order review page.');
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
			'value'      => $event->params['transaction']
		);
	}

	/**
	 * Returns a rendered html string to use for capturing user input
	 *
	 * @return string
	 */
	public function getOptionsHtml($context = array())
	{
		$context['statuses'] = $this->getAllTransactionStatuses();

		$options =  $context['options']['commerceStatuses'];
		$context['fieldValue'] = sproutEmail()->mailers->getCheckboxFieldValue($options);
		return craft()->templates->render('sproutemail/_events/saveTransaction', $context);
	}

	/**
	 * Returns the value that should be saved to options for the notification (registered event)
	 *
	 * @return mixed
	 */
	public function prepareOptions()
	{
		return array(
			'commerceStatuses' =>  craft()->request->getPost('commerceStatuses')
		);
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
	public function validateOptions($options, Commerce_TransactionModel $model, array $params = array())
	{
		// This will ensure to trigger the event after the payment is made
		if($model->reference != null)
		{
			$statuses = $options['commerceStatuses'];
			if(!empty($statuses))
			{
				if(is_string($statuses) && $statuses == "*")
				{
					return true;
				}

				if(is_array($statuses))
				{
					// Get first transaction which is the current transaction
					if (in_array($model->status, $statuses))
					{
						return true;
					}
				}
			}

			return false;
		}
		else
		{
			return false;
		}
	}

	public function getAllTransactionStatuses()
	{
		$statuses = [
			Commerce_TransactionRecord::STATUS_PENDING,
			Commerce_TransactionRecord::STATUS_REDIRECT,
			Commerce_TransactionRecord::STATUS_SUCCESS,
			Commerce_TransactionRecord::STATUS_FAILED
		];
		$options = array();
		if(!empty($statuses))
		{
			foreach($statuses as $status)
			{
				array_push(
					$options, array(
						'label' => ucwords($status),
						'value' => $status
					)
				);
			}
		}

		return $options;
	}

	/**
	 * @throws Exception
	 *
	 * @return BaseElementModel|null
	 */
	public function getMockedParams()
	{

		$order = craft()->sproutEmail_craftCommerce->getLatestRandomOrder();
		// Return the oldest order
		if (!empty($order))
		{
			$orderId = $order->id;
			$transactions = craft()->commerce_transactions->getAllTransactionsByOrderId($orderId);
			if(!empty($transactions))
			{
				return $transactions[0];
			}
		}

		return array();

	}
}