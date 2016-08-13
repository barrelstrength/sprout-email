<?php
namespace Craft;

class SproutEmail_UsersSaveUserEvent extends SproutEmailBaseEvent
{
	public function getName()
	{
		return 'users.saveUser';
	}

	public function getTitle()
	{
		return Craft::t('When a user is saved');
	}

	public function getDescription()
	{
		return Craft::t('Triggered when a user is saved.');
	}

	public function getOptionsHtml($context = array())
	{
		if (!isset($context['groups']))
		{
			$context['groups'] = $this->getAllGroups();
		}

		$options               = $context['options']['craft']['saveUser']['userGroupIds'];
		$context['fieldValue'] = sproutEmail()->mailers->getCheckboxFieldValue($options);

		return craft()->templates->render('sproutemail/_events/saveUser', $context);
	}

	public function prepareOptions()
	{
		$rules = craft()->request->getPost('rules.craft');

		return array(
			'craft' => $rules,
		);
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
		$isNewUser = isset($params['isNewUser']) && $params['isNewUser'];

		$whenNew = isset($options['craft']['saveUser']['whenNew']) &&
			$options['craft']['saveUser']['whenNew'];

		$whenUpdated = isset($options['craft']['saveUser']['whenUpdated']) &&
			$options['craft']['saveUser']['whenUpdated'];

		SproutEmailPlugin::log(Craft::t("Sprout Email '" . $this->getTitle() . "' event has been triggered"));

		// If any user groups were checked, make sure the user is in one of the groups
		if (is_array($options['craft']['saveUser']['userGroupIds']) && !empty($options['craft']['saveUser']['userGroupIds']) && count($options['craft']['saveUser']['userGroupIds']))
		{
			$inGroup            = false;
			$existingUserGroups = $user->getGroups('id');

			// When saving a new user, we grab our groups from the post request
			// because _processUserGroupsPermissions() runs after saveUser()
			$newUserGroups = craft()->request->getPost('groups');

			if (!is_array($newUserGroups))
			{
				$newUserGroups = ArrayHelper::stringToArray($newUserGroups);
			}

			foreach ($options['craft']['saveUser']['userGroupIds'] as $groupId)
			{
				if (array_key_exists($groupId, $existingUserGroups) || in_array($groupId, $newUserGroups))
				{
					$inGroup = true;
				}
			}

			if (!$inGroup)
			{
				SproutEmailPlugin::log(Craft::t('Saved user not in any selected User Group.'));

				return false;
			}
		}

		if (!$whenNew && !$whenUpdated)
		{
			SproutEmailPlugin::log(Craft::t("No settings have been selected. Please select 'When a user is created' or 'When
			a user is updated' from the options on the Rules tab."));

			return false;
		}

		if (($whenNew && !$isNewUser) && !$whenUpdated)
		{
			SproutEmailPlugin::log(Craft::t("No match. 'When a user is created' is selected but the user is being updated."));

			return false;
		}

		if (($whenUpdated && $isNewUser) && !$whenNew)
		{
			SproutEmailPlugin::log(Craft::t("No match. 'When a user is updated' is selected but the user is new."));

			return false;
		}

		// If user groups settings are unchecked
		if ($options['craft']['saveUser']['userGroupIds'] == '')
		{
			return false;
		}

		return true;
	}

	public function prepareParams(Event $event)
	{
		return array('value' => $event->params['user'], 'isNewUser' => $event->params['isNewUser']);
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

		if (isset($this->options['craft']['saveUser']['userGroupIds']) && count($this->options['craft']['saveUser']['userGroupIds']))
		{
			$ids = $this->options['craft']['saveUser']['userGroupIds'];

			if (is_array($ids) && count($ids))
			{
				$id = array_shift($ids);

				$criteria->groupId = $id;
			}
		}

		return $criteria->first();
	}

	/**
	 * Returns an array of groups suitable for use in checkbox field
	 *
	 * @return array
	 */
	public function getAllGroups()
	{
		try
		{
			$groups = craft()->userGroups->getAllGroups();
		}
		catch (\Exception $e)
		{
			$groups = array();
		}

		$options = array();

		if (count($groups))
		{
			foreach ($groups as $key => $group)
			{
				array_push(
					$options, array(
						'label' => $group->name,
						'value' => $group->id
					)
				);
			}
		}

		return $options;
	}
}
