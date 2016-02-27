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
		return craft()->templates->render('sproutemail/_actions/setStatus');
	}

	/**
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return bool
	 */
	public function performAction(ElementCriteriaModel $criteria)
	{
		$status = $this->getParams()->status;
		$archived = $enable = 0;

		switch ($status)
		{
			case SproutEmail_EntryModel::ENABLED:
			{
				$enable = 1;
				break;
			}
			case SproutEmail_EntryModel::DISABLED:
			{
				$enable = 0;
				break;
			}
			case SproutEmail_EntryModel::ARCHIVED:
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

		if ($status == SproutEmail_EntryModel::ENABLED)
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
					BaseElementModel::DISABLED,
					SproutEmail_EntryModel::ARCHIVED
				),
				'required' => true
			)
		);
	}
}
