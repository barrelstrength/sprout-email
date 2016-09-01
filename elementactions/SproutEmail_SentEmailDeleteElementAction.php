<?php
namespace Craft;

/**
 * Class SproutEmail_SentEmailDeleteElementAction
 *
 * @package Craft
 */
class SproutEmail_SentEmailDeleteElementAction extends DeleteElementAction
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
