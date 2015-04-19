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
		return Craft::t('User Login');
	}

	public function getDescription()
	{
		return Craft::t('Triggered when a user logs in.');
	}

	public function prepareParams(Event $event)
	{
		$user = craft()->users->getUserByUsernameOrEmail($event->params['username']);

		if (!$user)
		{
			sproutEmail()->error('No user found with username/email '.$event->params['username']);

			return null;
		}

		return array('value' => $user);
	}
}
