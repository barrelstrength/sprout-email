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
	 * @return null|string
	 */
	public function getConfirmationMessage()
	{
		return Craft::t('Are you sure you want to delete the selected emails?');
	}

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