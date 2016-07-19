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

		$elementType = $criteria->getElementType();

		if (!empty($ids))
		{
			foreach ($ids as $id)
			{
				if (is_a($elementType, "\\Craft\\SproutEmail_CampaignEmailElementType"))
				{
					$campaignEmail = sproutEmail()->campaignEmails->getCampaignEmailById($id);
					sproutEmail()->campaignEmails->deleteCampaignEmail($campaignEmail);
				}

				if (is_a($elementType, "\\Craft\\SproutEmail_NotificationEmailElementType"))
				{
					// Delete notification and related notification settings
					sproutEmail()->notificationEmails->deleteNotificationEmailById($id);
				}
			}
		}

		parent::performAction($criteria);

		return true;
	}
}
