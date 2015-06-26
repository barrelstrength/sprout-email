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
		return Craft::t('When an entry is saved');
	}

	public function getDescription()
	{
		return Craft::t('Triggered when an entry is saved.');
	}

	public function getOptionsHtml($context = array())
	{
		if (!isset($context['availableSections']))
		{
			$context['availableSections'] = $this->getAllSections();
		}

		return craft()->templates->render('sproutemail/_events/saveEntry', $context);
	}

	public function prepareOptions()
	{
		return array(
			'entriesSaveEntrySectionIds'     => craft()->request->getPost('entriesSaveEntrySectionIds'),
			'entriesSaveEntryOnlyWhenNew'    => craft()->request->getPost('entriesSaveEntryOnlyWhenNew'),
			'entriesSaveEntryOnlyWhenNotNew' => craft()->request->getPost('entriesSaveEntryOnlyWhenNotNew'),
		);
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
		$isNewEntry     = isset($params['isNewEntry']) && $params['isNewEntry'];
		$onlyWhenNew    = isset($options['entriesSaveEntryOnlyWhenNew']) && $options['entriesSaveEntryOnlyWhenNew'];
		$onlyWhenNotNew = isset($options['entriesSaveEntryOnlyWhenNotNew']) && $options['entriesSaveEntryOnlyWhenNotNew'];

		// If any section ids were checked
		// Make sure the entry belongs in one of them
		if (!empty($options['entriesSaveEntrySectionIds']) && count($options['entriesSaveEntrySectionIds']))
		{
			if (!in_array($entry->getSection()->id, $options['entriesSaveEntrySectionIds']))
			{
				return false;
			}
		}

		// If only new entries was checked
		// Make sure the entry is new
		if ($onlyWhenNew && !$isNewEntry)
		{
			return false;
		}

		// If only not new entries was checked
		// Make sure the entry is not new
		if ($onlyWhenNotNew && $isNewEntry)
		{
			return false;
		}

		return true;
	}

	public function prepareParams(Event $event)
	{
		return array('value' => $event->params['entry'], 'isNewEntry' => $event->params['isNewEntry']);
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
		$criteria = craft()->elements->getCriteria(ElementType::Entry);

		if (isset($this->options['entriesSaveEntrySectionIds']) && count($this->options['entriesSaveEntrySectionIds']))
		{
			$ids = $this->options['entriesSaveEntrySectionIds'];

			if (is_array($ids) && count($ids))
			{
				$id = array_shift($ids);

				$criteria->sectionId = $id;
			}
		}

		return $criteria->first();
	}

	/**
	 * Returns an array of sections suitable for use in checkbox field
	 *
	 * @return array
	 */
	protected function getAllSections()
	{
		$result  = craft()->sections->getAllSections();
		$options = array();

		foreach ($result as $key => $section)
		{
			array_push(
				$options, array(
					'label' => $section->name,
					'value' => $section->id
				)
			);
		}

		return $options;
	}
}
