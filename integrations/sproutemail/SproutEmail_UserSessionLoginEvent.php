<?php

namespace Craft;

class SproutEmail_UserSessionLoginEvent extends SproutEmailBaseEvent
{
	public function getName()
	{
		return 'userSession.login';
	}

	public function getTitle()
	{
		return Craft::t('When a user logs in');
	}

	public function getDescription()
	{
		return Craft::t('Triggered when a user logs in.');
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
		$user = craft()->users->getUserByUsernameOrEmail($event->params['username']);

		if ($user)
		{
			return array('value' => $user);
		}

		sproutEmail()->error('No user found with username/email ' . $event->params['username']);
	}

	/**
	 * @throws Exception
	 *
	 * @return BaseElementModel|null
	 */
	public function getMockedParams()
	{
		return craft()->elements->getCriteria(ElementType::User)->first(array('limit' => 1));
	}
}
