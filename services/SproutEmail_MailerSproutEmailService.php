<?php
namespace Craft;

class SproutEmail_MailerSproutEmailService extends BaseApplicationComponent
{
	/**
	 * @var array
	 */
	protected $settings;

	public function getRecipientLists($mailer)
	{
		$lists	= array();
		$groups = SproutEmail_RecipientGroupRecord::model()->findAllByAttributes(array('mailer' => $mailer));

		if ($groups)
		{
			foreach ($groups as $group)
			{
				$lists[] = array('label' => ucwords($group->name), 'value' => $group->id);
			}
		}

		return $lists;
	}

	public function getRecipientListById($id)
	{
		return SproutEmail_RecipientGroupRecord::model()->findById($id);
	}
}
