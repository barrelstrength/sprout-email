<?php
namespace Craft;

class SproutEmail_EntriesSaveEntryEvent extends SproutEmailBaseEvent
{
	public function getName()
	{
		return 'entries.saveEntry';
	}

	public function getTitle()
	{
		return 'Craft On Save Entry';
	}

	public function getDescription()
	{
		return 'Triggered when an entry is saved.';
	}

	public function getOptionsHtml($context = array())
	{
		return craft()->templates->render('sproutemail/_events/saveEntry', $context);
	}

	public function prepareOptions()
	{
		return craft()->request->getPost('entriesSaveEntrySectionIds');
	}

	public function prepareParams(Event $event)
	{
		return array('value' => $event->params['entry'], 'isNewEntry' => $event->params['isNewEntry']);
	}

	public function prepareValue($value)
	{
		return array('entriesSaveEntrySectionIds' => $value);
	}
}
