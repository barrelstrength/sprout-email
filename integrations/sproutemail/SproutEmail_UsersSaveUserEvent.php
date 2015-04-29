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
		return Craft::t('Save User');
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

		return craft()->templates->render('sproutemail/_events/saveUser', $context);
	}

	public function prepareOptions()
	{
		return array(
			'usersSaveUserGroupIds'    => craft()->request->getPost('usersSaveUserGroupIds'),
			'usersSaveUserOnlyWhenNew' => craft()->request->getPost('usersSaveUserOnlyWhenNew'),
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
		$isNewUser   = isset($params['isNewUser']) && $params['isNewUser'];
		$onlyWhenNew = isset($options['usersSaveUserOnlyWhenNew']) && $options['usersSaveUserOnlyWhenNew'];

		// If any user groups were checked
		// Make sure the user is in one of the groups
		if (!empty($options['usersSaveUserGroupIds']) && count($options['usersSaveUserGroupIds']))
		{
			$inGroup    = false;
			$userGroups = $user->getGroups('id');

			foreach ($options['usersSaveUserGroupIds'] as $groupId)
			{
				if (array_key_exists($groupId, $userGroups))
				{
					$inGroup = true;
				}
			}

			if (!$inGroup)
			{
				return false;
			}
		}

		// If only new users was checked
		// Make sure this user is new
		if (!$onlyWhenNew || ($onlyWhenNew && $isNewUser))
		{
			return true;
		}

		return false;
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

		if (isset($this->options['usersSaveUserGroupIds']) && count($this->options['usersSaveUserGroupIds']))
		{
			$ids = $this->options['usersSaveUserGroupIds'];

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
	protected function getAllGroups()
	{
		$result  = craft()->userGroups->getAllGroups();
		$options = array();

		foreach ($result as $key => $group)
		{
			array_push(
				$options, array(
					'label' => $group->name,
					'value' => $group->id
				)
			);
		}

		return $options;
	}
}
