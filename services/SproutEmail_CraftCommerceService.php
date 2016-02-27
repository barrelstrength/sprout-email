<?php

namespace Craft;

class SproutEmail_CraftCommerceService extends BaseApplicationComponent
{

	/**
	 * Get the first order of craft comerce being made.
	 *
	 * @return array|BaseElementModel|null
	 * @throws Exception
	 */
	public function getFirstOrder()
	{
		$criteria = craft()->elements->getCriteria("Commerce_Order");

		$criteria->order = 'id asc';
		$criteria->orderStatusId = 'not NULL';
		$criteria->limit = 1;

		// Return the oldest order
		if ($order = $criteria->first())
		{
			return $order;
		}
		else
		{
			return array();
		}
	}

	/**
	 * Get one order from the latest 15 orders.
	 *
	 * @return null
	 */
	public function getLatestRandomOrder()
	{
		$randomOrder = null;

		$ids = $this->getOrderIds();

		if (!empty($ids))
		{
			$randomId = $ids[array_rand($ids)];

			$randomOrder = craft()->commerce_orders->getOrderById($randomId);
		}

		return $randomOrder;
	}

	public function getOrderIds($limit = 15)
	{
		$criteria = craft()->elements->getCriteria("Commerce_Order");

		$criteria->order = 'id desc';
		$criteria->orderStatusId = 'not NULL';
		$criteria->limit = $limit;

		$ids = array();

		$orders = $criteria->find();

		if (!empty($orders))
		{
			foreach ($orders as $order)
			{
				$ids[] = $order->id;
			}
		}

		return $ids;
	}

}