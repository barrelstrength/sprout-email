<?php

namespace Craft;

class SproutEmail_UsersDeleteUserEvent extends SproutEmailBaseEvent
{
	public function getName()
	{
		return 'users.deleteUser';
	}

	public function getTitle()
	{
		return Craft::t('When a user is deleted');
	}

	public function getDescription()
	{
		return Craft::t('Triggered when a user is deleted.');
	}

	/**
	 * Returns whether or not the user meets the criteria necessary to trigger the event
	 *
	 * @param mixed     $options
	 * @param UserModel $user
	 * @param array     $params
	 *
	 * @return bool
	 */
	public function validateOptions($options, UserModel $user, array $params = array())
	{
		SproutEmailPlugin::log(Craft::t("Sprout Email '" . $this->getTitle() . "' event has been triggered"));

		return true;
	}

	public function prepareParams(Event $event)
	{
		return array('value' => $event->params['user']);
	}

	public function prepareValue($value)
	{
		return $value;
	}

	/**
	 * @throws Exception
	 *
	 * @return BaseElementModel|null
	 */
	public function getMockedParams()
	{
		$criteria = craft()->elements->getCriteria(ElementType::User);

		return $criteria->first();
	}
}
