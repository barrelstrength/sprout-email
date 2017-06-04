<?php

namespace Craft;

/**
 * Class SproutEmail_NotificationEmailDeleteElementAction
 *
 * @package Craft
 */
class SproutEmail_NotificationEmailDeleteElementAction extends DeleteElementAction
{

	/**
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return bool
	 */
	public function performAction(ElementCriteriaModel $criteria)
	{
		parent::performAction($criteria);

		return true;
	}
}
