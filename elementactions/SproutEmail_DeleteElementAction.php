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

		if (!empty($ids))
		{
			foreach ($ids as $id)
			{
				if ($campaign = sproutEmail()->campaigns->getCampaignByEntryId($id))
				{
					if ($campaign->type == 'notification')
					{
						// Delete notification and related notification settings
						sproutEmail()->campaigns->deleteCampaign($campaign->id);
					}

					if ($campaign->type == 'email')
					{
						$entry = sproutEmail()->entries->getEntryById($id);
						sproutEmail()->entries->deleteEntry($entry);
					}
				}
			}
		}

		parent::performAction($criteria);

		return true;
	}
}
