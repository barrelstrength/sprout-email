<?php
namespace Craft;

class SproutEmail_SproutEmailMailer extends SproutEmailBaseMailer
{
	protected $service;

	public function getService()
	{
		if (null === $this->service)
		{
			$this->service = Craft::app()->getComponent('sproutEmail_mailerSproutEmail');
		}

		return $this->service;
	}

	public function getTitle()
	{
		return 'Sprout Email Service';
	}

	public function getName()
	{
		return 'sproutemail';
	}

	public function getDefaultSettings()
	{
		return array(
			'fromName'  => '',
			'fromEmail' => '',
		    'replyToEmail' => '',
		);
	}

	public function getRecipientListById($id)
	{
		return $this->getService()->getRecipientListById($id);
	}

	public function getRecipientLists()
	{
		return $this->getService()->getRecipientLists($this->getId());
	}

	/**
	 * Renders the recipient list UI for this mailer
	 *
	 * @return string|\Twig_Markup
	 */
	public function getRecipientListsHtml($selected=null)
	{
		if (($lists = $this->getRecipientLists()))
		{
			return craft()->templates->renderMacro('_includes/forms', 'selectField',
				array(
					array(
						'id'	=> 'recipientsList',
						'name'	=> 'recipientsList',
						'label' => 'Select Recipient List',
						'options' => $lists,
						'value' => $selected,
					)
				)
			);
		}
	}

	public function prepareRecipientList(SproutEmail_EntryModel $entry, SproutEmail_CampaignModel $campaign)
	{
		$id = craft()->request->getPost('recipientsList');

		if ($id && ($list = $this->getRecipientListById($id)))
		{
			$model = new SproutEmail_EntryRecipientListModel();

			$model->setAttribute('entryId', $entry->id);
			$model->setAttribute('mailer', $this->getId());
			$model->setAttribute('list', $list->name);
			$model->setAttribute('type', $campaign->type);

			return $model;
		}
	}
}
