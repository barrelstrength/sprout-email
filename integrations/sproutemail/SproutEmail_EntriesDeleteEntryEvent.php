<?php
namespace Craft;

class SproutEmail_EntriesDeleteEntryEvent extends SproutEmailBaseEvent
{
	public function getName()
	{
		return 'entries.deleteEntry';
	}

	public function getTitle()
	{
		return Craft::t('When an entry is deleted');
	}

	public function getDescription()
	{
		return Craft::t('Triggered when an entry is deleted.');
	}

	/**
	 * Returns whether or not the entry meets the criteria necessary to trigger the event
	 *
	 * @param mixed      $options
	 * @param EntryModel $entry
	 * @param array      $params
	 *
	 * @return bool
	 */
	public function validateOptions($options, EntryModel $entry, array $params = array())
	{
		return true;
	}

	public function prepareParams(Event $event)
	{
		return array('value' => $event->params['entry']);
	}

	public function prepareValue($value)
	{
		return $value;
	}

	/**
	 * Returns a random entry to represent an entry that could have been deleted
	 *
	 * @throws Exception
	 *
	 * @return BaseElementModel|null
	 */
	public function getMockedParams()
	{
		$criteria = craft()->elements->getCriteria(ElementType::Entry);

		return $criteria->first();
	}
}
