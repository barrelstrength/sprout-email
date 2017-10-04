<?php
namespace Craft;

/**
 * Class SproutEmail_CampaignEmailDeleteElementAction
 *
 * @package Craft
 */
class SproutEmail_CampaignEmailDeleteElementAction extends DeleteElementAction
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
