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

	public function validateOptions($options, $order, array $params = array())
	{
		$userId = $params['userId'];

		$groups = craft()->userGroups->getGroupsByUserId($userId);

		$oldGroups = array();

		if(!empty($groups))
		{
			foreach($groups as $group)
			{
				$oldGroups[] = $group->id;
			}
		}

		$newGroups = $params['groupIds'];

		$optionOld = $options['assignedGroups']['old'];
		$optionNew = $options['assignedGroups']['new'];

		$resultOld = sproutEmail()->mailers->isArraySettingsMatch($oldGroups, $optionOld);

		$resultNew = sproutEmail()->mailers->isArraySettingsMatch($newGroups, $optionNew);

		if($resultOld == true && $resultNew == true)
		{
			return true;
		}

		return false;
	}
}