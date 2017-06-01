<?php
namespace Craft;

/**
 * Class SproutEmail_MarkSentDeleteElementAction
 *
 * @package Craft
 */
class SproutEmail_MarkSentDeleteElementAction extends BaseElementAction
{
	/**
	 * @return string
	 */
	public function getTriggerHtml()
	{
		return craft()->templates->render('sproutemail/_components/elementactions/setSent');
	}

	/**
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return bool
	 */
	public function performAction(ElementCriteriaModel $criteria)
	{
		$param = $this->getParams()->param;

		switch ($param)
		{
			case "delete":
					craft()->elements->deleteElementById($criteria->ids());

					$this->setMessage(Craft::t('Campaign emails deleted.'));
				break;

			case "sent":

				// Update lastDateSent current time
				craft()->db->createCommand()->update(
					'sproutemail_campaignemails',
					array('lastDateSent' => DateTimeHelper::currentTimeForDb()),
					array('in', 'id', $criteria->ids())
				);

				$this->setMessage(Craft::t('Campaign emails marked as sent.'));
				break;
		}

		return true;
	}

	/**
	 * @return array
	 */
	protected function defineParams()
	{
		return array(
			'param' => array(
				AttributeType::Enum,
				'values'   => array(
					SproutEmail_CampaignEmailModel::SENT,
					'delete'
				),
				'required' => true
			)
		);
	}
}
