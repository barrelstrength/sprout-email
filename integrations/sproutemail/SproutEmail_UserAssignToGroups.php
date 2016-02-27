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

	public function prepareParams(Event $event)
	{
		$values = array();
		$userId = $event->params['userId'];

		$user = craft()->users->getUserById($userId);

		$values['value']['user'] = $user;

		$groups = craft()->userGroups->getGroupsByUserId($userId);

		$oldNames = array();
		if (!empty($groups))
		{
			foreach ($groups as $group)
			{
				$oldNames[] = $group->name;
			}
		}

		$groupIds = $event->params['groupIds'];
		$newNames = array();
		if (!empty($groupIds))
		{
			foreach ($groupIds as $id)
			{
				$groupModel = craft()->userGroups->getGroupById($id);
				$newNames[] = $groupModel->name;
			}
		}

		$values['value']['oldgroups'] = $oldNames;
		$values['value']['newgroups'] = $newNames;
		$values['value']['groupIds'] = $groupIds;

		return $values;
	}

	public function prepareOptions()
	{
		$assignedGroups = craft()->request->getPost('assignedGroups');

		return array(
			'assignedGroups' => $assignedGroups
		);
	}

	public function getOptionsHtml($context = array())
	{
		if (!isset($context['groups']))
		{
			$context['groups'] = craft()->sproutEmail_defaultMailer->getAllGroupsOptions();
		}

		$oldGroups = $context['options']['assignedGroups']['old'];
		$context['oldFieldValue'] = sproutEmail()->mailers->getCheckboxFieldValue($oldGroups);

		$newGroups = $context['options']['assignedGroups']['new'];
		$context['newFieldValue'] = sproutEmail()->mailers->getCheckboxFieldValue($newGroups);

		return craft()->templates->render('sproutemail/_events/userAssignGroups', $context);
	}

	public function validateOptions($options, $values, array $params = array())
	{

		$userId = $values['user']->id;

		$groups = craft()->userGroups->getGroupsByUserId($userId);

		$oldGroups = array();

		if (!empty($groups))
		{
			foreach ($groups as $group)
			{
				$oldGroups[] = $group->id;
			}
		}

		$newGroups = $values['groupIds'];

		$optionOld = $options['assignedGroups']['old'];
		$optionNew = $options['assignedGroups']['new'];

		$resultOld = sproutEmail()->mailers->isArraySettingsMatch($oldGroups, $optionOld);

		$resultNew = sproutEmail()->mailers->isArraySettingsMatch($newGroups, $optionNew);

		if ($resultOld == true && $resultNew == true)
		{
			return true;
		}

		return false;
	}
}