<?php

namespace Craft;

/**
 * Class SproutEmail_MarkSentElementAction
 *
 * @package Craft
 */
class SproutEmail_MarkSentElementAction extends BaseElementAction
{
	/**
	 * @return null|string
	 */
	public function getName()
	{
		return Craft::t('Mark as Sent');
	}

	/**
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return bool
	 */
	public function performAction(ElementCriteriaModel $criteria)
	{
		// Update lastDateSent with current DateTime
		craft()->db->createCommand()->update(
			'sproutemail_campaignemails',
			array('lastDateSent' => DateTimeHelper::currentTimeForDb()),
			array('in', 'id', $criteria->ids())
		);

		$this->setMessage(Craft::t('Campaign emails marked as sent.'));

		return true;
	}
}
