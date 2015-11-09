<?php
namespace Craft;

/**
 * Class SproutEmail_SetStatusElementAction
 *
 * @package Craft
 */
class SproutEmail_DeleteElementAction extends DeleteElementAction
{

	/**
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return bool
	 */
	public function performAction(ElementCriteriaModel $criteria)
	{
		$ids = $criteria->ids();

		// Delete related campaign model
		if(!empty($ids))
		{
			foreach($ids as $id)
			{

				if($campaign = sproutEmail()->campaigns->getCampaignByEntryId($id))
				{
					sproutEmail()->campaigns->deleteCampaign($campaign->id);
				}

			}
		}

		parent::performAction($criteria);

		return true;
	}

}
