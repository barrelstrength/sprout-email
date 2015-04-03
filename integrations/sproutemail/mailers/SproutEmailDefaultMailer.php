<?php
namespace Craft;

class SproutEmailDefaultMailer extends SproutEmailBaseMailer implements SproutEmailNotificationSenderInterface
{
	/**
	 * @var SproutEmail_DefaultMailerService
	 */
	protected $service;

	/**
	 * @return SproutEmail_DefaultMailerService
	 */
	public function getService()
	{
		if (is_null($this->service))
		{
			$this->service = Craft::app()->getComponent('sproutEmail_defaultMailer');
		}

		return $this->service;
	}

	public function getName()
	{
		return 'defaultmailer';
	}

	public function getTitle()
	{
		return 'Sprout Email';
	}

	public function getDescription()
	{
		return Craft::t('The default mailer for Sprout Email');
	}

	public function getSettingsHtml(array $context = array())
	{
		if (!isset($context['settings']) || $context['settings'] === null)
		{
			$context['settings'] = $this->getSettings();
		}

		$html = craft()->templates->render('sproutemail/defaultmailer/_settings', $context);

		return TemplateHelper::getRaw($html);
	}

	/**
	 * @param $recipientListHandle
	 *
	 * @return SproutEmail_DefaultMailerRecipientModel[]
	 */
	public function getRecipients($recipientListHandle)
	{
		if (($list = $this->getService()->getRecipientListByHandle($recipientListHandle)))
		{
			return SproutEmail_DefaultMailerRecipientModel::populateModels($list->recipients);
		}
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
	 * @param SproutEmail_EntryModel[] $values
	 *
	 * @return string|\Twig_Markup
	 */
	public function getRecipientListsHtml(array $values = null)
	{
		$lists    = $this->getRecipientLists();
		$options  = array();
		$selected = array();

		if (!count($lists))
		{
			return craft()->templates->render('sproutemail/defaultmailer/recipientlists/_norecipientlists');
		}

		foreach ($lists as $list)
		{
			$options[] = array(
				'label' => $list->name,
				'value' => $list->id
			);
		}

		if (is_array($values) && count($values))
		{
			foreach ($values as $value)
			{
				$selected[] = $value->list;
			}
		}

		$html = craft()->templates->renderMacro(
			'_includes/forms', 'checkboxGroup', array(
				array(
					'id'      => 'recipientLists',
					'name'    => 'recipient[recipientLists]',
					'options' => $options,
					'values'  => $selected,
				)
			)
		);

		return TemplateHelper::getRaw($html);
	}

	public function defineSettings()
	{
		return array(
			'fromName'  => array(AttributeType::String, 'required' => true),
			'fromEmail' => array(AttributeType::Email, 'required' => true),
			'replyTo'   => array(AttributeType::Email, 'required' => false),
		);
	}

	public function hasCpSection()
	{
		return true;
	}

	public function prepareRecipientLists(SproutEmail_EntryModel $entry, SproutEmail_CampaignModel $campaign)
	{
		$ids   = craft()->request->getPost('recipient.recipientLists');
		$lists = array();

		if ($ids)
		{
			foreach ($ids as $id)
			{
				$model = new SproutEmail_EntryRecipientListModel();

				$model->setAttribute('entryId', $entry->id);
				$model->setAttribute('mailer', $this->getId());
				$model->setAttribute('list', $id);
				$model->setAttribute('type', $campaign->type);

				$lists[] = $model;
			}
		}

		return $lists;
	}

	public function sendNotification(SproutEmail_CampaignModel $campaign, $element = null)
	{
		return $this->getService()->sendNotification($campaign, $element);
	}

	/**
	 * @param SproutEmail_EntryModel    $entry
	 * @param SproutEmail_CampaignModel $campaign
	 *
	 * @return array
	 */
	public function exportEntry(SproutEmail_EntryModel $entry, SproutEmail_CampaignModel $campaign)
	{
		$success = false;

		try
		{
			$this->getService()->exportEntry($entry, $campaign);

			$success = true;
		}
		catch (\Exception $e)
		{
			sproutEmail()->error($e->getMessage());
		}

		$content = craft()->templates->render(
			'sproutemail/defaultmailer/modals/_export',
			array(
				'entry'    => $entry,
				'campaign' => $campaign,
				'success'  => $success,
			)
		);

		return compact('content');
	}

	/**
	 * @param SproutEmail_EntryModel    $entry
	 * @param SproutEmail_CampaignModel $campaign
	 *
	 * @return array
	 */
	public function previewEntry(SproutEmail_EntryModel $entry, SproutEmail_CampaignModel $campaign)
	{
		$success = false;

		try
		{
			$this->getService()->exportEntry($entry, $campaign);

			$success = true;
		}
		catch (\Exception $e)
		{
			sproutEmail()->error($e->getMessage());
		}

		$content = craft()->templates->render(
			'sproutemail/defaultmailer/modals/_export',
			array(
				'entry'    => $entry,
				'campaign' => $campaign,
				'success'  => $success,
			)
		);

		return compact('content');
	}

	/**
	 * @param SproutEmail_EntryModel    $entry
	 * @param SproutEmail_CampaignModel $campaign
	 *
	 * @return string
	 */
	public function getPrepareModalHtml(SproutEmail_EntryModel $entry, SproutEmail_CampaignModel $campaign)
	{
		$lists          = sproutEmail()->entries->getRecipientListsByEntryId($entry->id);
		$recipientLists = array();

		if (count($lists))
		{
			foreach ($lists as $list)
			{
				$recipientList = sproutEmailDefaultMailer()->getRecipientListById($list->list);

				if ($recipientList)
				{
					$recipientLists[] = $recipientList;
				}
			}
		}

		return craft()->templates->render(
			'sproutemail/defaultmailer/modals/_prepare',
			array(
				'entry'          => $entry,
				'campaign'       => $campaign,
				'recipientLists' => $recipientLists
			)
		);
	}
}
