<?php

namespace Craft;

class SproutEmail_UserAssignToGroups extends SproutEmailBaseEvent
{

	public function getName()
	{
		return 'userGroups.onBeforeAssignUserToGroups';
	}

	public function getTitle()
	{
		return Craft::t('When a user is assigned to a group');
	}

	public function getDescription()
	{
		return Craft::t('Triggered when a user is assigned to a group.');
	}

	public function getOptionsHtml($context = array())
	{
		if (!isset($context['groups']))
		{
			$context['groups'] = craft()->sproutEmail_defaultMailer->getAllGroupsOptions();
		}

		return craft()->templates->render('sproutemail/_events/userAssignGroups', $context);
	}

	public function prepareOptions()
	{
		$rules = craft()->request->getPost('rules.craft');

		return array(
			'craft' => $rules,
		);
	}
}