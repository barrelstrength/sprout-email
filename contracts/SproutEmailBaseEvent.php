<?php
namespace Craft;

class SproutEmailBaseEvent
{
	/**
	 * Returns the event title when used in string context
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->getTitle();
	}

	/**
	 * Returns the event id to use as the formal identifier
	 *
	 * @example entries-saveEntry
	 *
	 * @return string
	 */
	public function getId()
	{
		return str_replace('.', '-', $this->getName());
	}

	/**
	 * Returns the qualified event name to use when registering with crat()->on
	 *
	 * @example entries.saveEntry
	 *
	 * @return string
	 */
	public function getName() {}

	/**
	 * Returns the event title to use when displaying a label or similar use case
	 *
	 * @example Craft Save Entry
	 *
	 * @return string
	 */
	public function getTitle() {}

	/**
	 * Returns a short description of this event
	 *
	 * @example Triggers when an entry is saved
	 *
	 * @return string
	 */
	public function getDescription() {}

	/**
	 * Returns a rendered html string to use for capturing user
	 *
	 * @example
	 * <h3>Select Sections</h3>
	 * <p>Please select what sections you whant the save entry event to trigger on</p>
	 * <input type="checkbox" id="sectionIds[]" value="1">
	 * <input type="checkbox" id="sectionsIds[]" value="2">
	 *
	 * @return string
	 */
	public function getOptionsHtml() {}

	/**
	 * Returns the value that should be saved to options for the notification (registered event)
	 *
	 * @example
	 * return craft()->request->getPost('sectionIds');
	 *
	 * @return mixed
	 */
	public function prepareOptions() {}

	/**
	 * Returns the data passed in by the triggered event
	 *
	 * @example
	 * return $event->params['entry'];
	 *
	 * @param Event $event
	 *
	 * @return mixed
	 */
	public function prepareParams(Event $event)
	{
		return $event->params;
	}
}
