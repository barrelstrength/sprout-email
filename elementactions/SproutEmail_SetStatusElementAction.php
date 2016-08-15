<?php
namespace Craft;

/**
 * Class SproutEmail_SetStatusElementAction
 *
 * @package Craft
 */
class SproutEmail_SetStatusElementAction extends BaseElementAction
{
	/**
	 * @return string
	 */
	public function getTriggerHtml()
	{
		return craft()->templates->render('sproutemail/_components/elementactions/setStatus');
	}

	/**
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return bool
	 */
	public function performAction(ElementCriteriaModel $criteria)
	{

		$status   = $this->getParams()->status;
		$archived = 0;
		$enable   = 0;

		switch ($status)
		{
			case SproutEmail_CampaignEmailModel::ENABLED:
			{
				$enable = 1;
				break;
			}
			case SproutEmail_CampaignEmailModel::DISABLED:
			{
				$enable = 0;
				break;
			}
			case SproutEmail_CampaignEmailModel::ARCHIVED:
			{
				$archived = 1;
				break;
			}
		}

		$elementIds = $criteria->ids();

		// Update their statuses
		craft()->db->createCommand()->update(
			'elements',
			array('enabled' => $enable, 'archived' => $archived),
			array('in', 'id', $elementIds)
		);

		if ($status == SproutEmail_CampaignEmailModel::ENABLED)
		{
			// Enable their locale as well
			craft()->db->createCommand()->update(
				'elements_i18n',
				array('enabled' => $enable),
				array('and', array('in', 'elementId', $elementIds), 'locale = :locale'),
				array(':locale' => $criteria->locale)
			);
		}

		// Clear their template caches
		craft()->templateCache->deleteCachesByElementId($elementIds);

		// Trigger an 'onSetStatus' event
		$event = new Event($this, array(
			'criteria'   => $criteria,
			'elementIds' => $elementIds,
			'status'     => $status
		));

		sproutEmail()->onSetStatus($event);

		$this->setMessage(Craft::t('Statuses updated.'));

		return true;
	}

	/**
	 * @return array
	 */
	protected function defineParams()
	{
		return array(
			'status' => array(
				AttributeType::Enum,
				'values'   => array(
					BaseElementModel::ENABLED,
					BaseElementModel::DISABLED,
					SproutEmail_CampaignEmailModel::ARCHIVED
				),
				'required' => true
			)
		);
	}
}
