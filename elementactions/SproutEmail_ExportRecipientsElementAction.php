<?php
namespace Craft;

class SproutEmail_ExportRecipientsElementAction extends BaseElementAction
{
	/**
	 * @inheritDoc IComponentType::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Export...');
	}

	/**
	 * @inheritDoc IElementAction::isDestructive()
	 *
	 * @return bool
	 */
	public function isDestructive()
	{
		return false;
	}

	/**
	 * @inheritDoc IElementAction::getConfirmationMessage()
	 *
	 * @return string|null
	 */
	public function getConfirmationMessage()
	{
		return $this->getParams()->confirmationMessage;
	}

	/**
	 * @inheritDoc IElementAction::performAction()
	 *
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return bool
	 */
	public function performAction(ElementCriteriaModel $criteria)
	{
		craft()->httpSession->add('__exportJob', $criteria->getAttributes());

		$this->setMessage($this->getParams()->successMessage);

		craft()->templates->includeJs('
			var exportUrl = Craft.getActionUrl("sproutEmail/defaultMailer/exportCsv");
			setTimeout(function() { window.location=exportUrl; }, 1500);
		');

		return true;
	}

	/**
	 * @inheritDoc BaseElementAction::defineParams()
	 *
	 * @return array
	 */
	protected function defineParams()
	{
		return array(
			'confirmationMessage' => array(AttributeType::String),
			'successMessage'      => array(AttributeType::String),
		);
	}
}
