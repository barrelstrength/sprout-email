<?php
namespace Craft;

class SproutEmail_CraftCommerceService extends BaseApplicationComponent
{
	/**
	 * Get a recent Craft Commerce Order
	 *
	 * @return Commerce_OrderModel
	 */
	public function getRecentOrder()
	{
		$order = null;
		$orderIds = $this->getCraftCommerceOrderIds();

		if (!empty($orderIds))
		{
			$randomIndex = array_rand($orderIds);
			$randomOrderId = $orderIds[$randomIndex];

			$order = craft()->commerce_orders->getOrderById($randomOrderId);
		}

		return $order;
	}

	public function getCraftCommerceOrderIds($limit = 15)
	{
		$criteria = craft()->elements->getCriteria("Commerce_Order");
		$criteria->order = 'id desc';
		$criteria->orderStatusId = 'not NULL';
		$criteria->limit = $limit;

		$orderIds = $criteria->ids();

		return $orderIds;
	}
}