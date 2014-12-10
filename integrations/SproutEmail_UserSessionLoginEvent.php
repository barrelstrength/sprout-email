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
		return 'Craft On User Login';
	}

	public function getDescription()
	{
		return 'Triggered when a user logs in.';
	}

	public function prepareParams(Event $event)
	{
		return array('value' => $event->params['username']);
	}

	public function prepareValue($value)
	{
		return array('entriesSaveEntrySectionIds' => $value);
	}
}
