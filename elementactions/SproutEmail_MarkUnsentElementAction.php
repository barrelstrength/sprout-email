<?php

namespace Craft;

/**
 * Class SproutEmail_MarkUnsentElementAction
 *
 * @package Craft
 */
class SproutEmail_MarkUnsentElementAction extends BaseElementAction
{
	/**
	 * @return null|string
	 */
	public function getName()
	{
		return Craft::t('Mark as Unsent');
	}

	/**
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return bool
	 */
	public function performAction(ElementCriteriaModel $criteria)
	{
		// Update lastDateSent to null
		craft()->db->createCommand()->update(
			'sproutemail_campaignemails',
			array('lastDateSent' => null),
			array('in', 'id', $criteria->ids())
		);

		$this->setMessage(Craft::t('Campaign emails marked as unsent.'));

		return true;
	}
}
