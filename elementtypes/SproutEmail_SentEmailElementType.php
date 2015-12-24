<?php
namespace Craft;

class SproutEmail_SentEmailElementType extends BaseElementType
{
	/**
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Sprout Sent Email');
	}

	/**
	 * @return bool
	 */
	public function hasTitles()
	{
		return false;
	}

	/**
	 * @return bool
	 */
	public function hasContent()
	{
		return false;
	}

	/**
	 * @return bool
	 */
	public function isLocalized()
	{
		return false;
	}

	/**
	 * @inheritDoc IElementType::hasStatuses()
	 *
	 * @return bool
	 */
	public function hasStatuses()
	{
		return false;
	}


	/**
	 * Returns the attributes that can be selected as table columns
	 *
	 * @return array
	 */
	public function defineAvailableTableAttributes()
	{
		$attributes = array(
				'subject'       => array('label' => Craft::t('Subject')),
				'dateCreated' => array('label' => Craft::t('Date Created')),
				'dateUpdated' => array('label' => Craft::t('Date Updated')),

		);

		return $attributes;
	}

	/**
	 * Returns default table columns for table views
	 *
	 * @return array
	 */
	public function getDefaultTableAttributes($source = null)
	{
		$attributes = array();

		$attributes[] = 'subject';
		$attributes[] = 'fromEmail';
		$attributes[] = 'dateCreated';
		$attributes[] = 'dateUpdated';

		return $attributes;
	}

	/**
	 * Defines any custom element criteria attributes for this element type.
	 *
	 * @return array
	 */
	public function defineCriteriaAttributes()
	{
		return array(
			'subject'          => AttributeType::String,
			'fromEmail'    	   => AttributeType::String
		);
	}

	/**
	 * Defines which model attributes should be searchable.
	 *
	 * @return array
	 */
	public function defineSearchableAttributes()
	{
		return array('subject');
	}

	/**
	 * Modifies an element query targeting elements of this type.
	 *
	 * @param DbCommand            $query
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return mixed
	 */
	public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
	{
		// join with the table
		$query->addSelect('sentemail.*')
			->join('sproutemail_sentemail sentemail', 'sentemail.id = elements.id');
	}


	/**
	 * Populates an element model based on a query result.
	 *
	 * @param array $row
	 *
	 * @return array
	 */
	public function populateElementModel($row)
	{
		return SproutEmail_SentEmailModel::populateModel($row);
	}
}
