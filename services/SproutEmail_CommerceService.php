<?php

namespace Craft;


class SproutEmail_CommerceService extends BaseApplicationComponent
{

	public function getFirstOrder()
	{
		$criteria = craft()->elements->getCriteria("Commerce_Order");

		$criteria->order         = 'id asc';
		$criteria->orderStatusId = 'not NULL';

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

}