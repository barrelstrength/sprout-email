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

		$context['fieldValue'] = '';

		if (isset($context['options']['craft']['saveEntry']['sectionIds']))
		{
			$sectionOptions = $context['options']['craft']['saveEntry']['sectionIds'];

			$context['fieldValue'] = sproutEmail()->mailers->getCheckboxFieldValue($sectionOptions);
		}

		return craft()->templates->render('sproutemail/_integrations/events/saveEntry', $context);
	}

	public function prepareOptions()
	{
		$rules = craft()->request->getPost('rules.craft');

		return array(
			'craft' => $rules,
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
	public function validateOptions($options, $entry, array $params = array())
	{
		$isNewEntry = isset($params['isNewEntry']) && $params['isNewEntry'];

		$whenNew = isset($options['craft']['saveEntry']['whenNew']) &&
			$options['craft']['saveEntry']['whenNew'];

		$whenUpdated = isset($options['craft']['saveEntry']['whenUpdated']) &&
			$options['craft']['saveEntry']['whenUpdated'];

		SproutEmailPlugin::log(Craft::t("Sprout Email '" . $this->getTitle() . "' event has been triggered"));

		// If any section ids were checked, make sure the entry belongs in one of them
		if (is_array($options['craft']['saveEntry']['sectionIds']) && !empty($options['craft']['saveEntry']['sectionIds']) && count($options['craft']['saveEntry']['sectionIds']))
		{
			if (!in_array($entry->getSection()->id, $options['craft']['saveEntry']['sectionIds']))
			{
				SproutEmailPlugin::log(Craft::t('Saved entry not in any selected Section.'), LogLevel::Warning);

				return false;
			}
		}

		if (!$whenNew && !$whenUpdated)
		{
			SproutEmailPlugin::log(Craft::t("No settings have been selected. Please select 'When an entry is created' or 'When
			an entry is updated' from the options on the Rules tab."), LogLevel::Warning);

			return false;
		}

		// Make sure new entries are new
		if (($whenNew && !$isNewEntry) && !$whenUpdated)
		{
			SproutEmailPlugin::log(Craft::t("No match. 'When an entry is created' is selected but the entry is being updated
			."), LogLevel::Warning);

			return false;
		}

		// Make sure updated entries are not new
		if (($whenUpdated && $isNewEntry) && !$whenNew)
		{
			SproutEmailPlugin::log(Craft::t("No match. 'When an entry is updated' is selected but the entry is new."), LogLevel::Warning);

			return false;
		}

		// If entry sections settings are unchecked
		if ($options['craft']['saveEntry']['sectionIds'] == '')
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

		if (isset($this->options['craft']['saveEntry']['sectionIds']) && count($this->options['craft']['saveEntry']['sectionIds']))
		{
			$ids = $this->options['craft']['saveEntry']['sectionIds'];

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
			$options[] = array(
				'label' => $section->name,
				'value' => $section->id
			);
		}

		return $options;
	}
}
