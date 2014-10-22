<?php
namespace Craft;

class SproutEmail_EmailBlastElementType extends BaseElementType
{
	/**
	 * Returns the element type name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Sprout Email Blast');
	}

	/**
	 * Returns whether this element type has content.
	 *
	 * @return bool
	 */
	public function hasContent()
	{
		return true;
	}

	/**
	 * Returns whether this element type has titles.
	 *
	 * @return bool
	 */
	public function hasTitles()
	{
		return true;
	}

	/**
	 * Returns whether this element type stores data on a per-locale basis.
	 *
	 * @return bool
	 */
	public function isLocalized()
	{
		return false;
	}

	/**
	 * Returns this element type's sources.
	 *
	 * @param string|null $context
	 * @return array|false
	 */
	public function getSources($context = null)
	{
		// Start with an option for everything
		$sources = array(
			'*' => array(
				'label'    => Craft::t('All Email Blasts'),
			)
		);

		// Prepare the data for our sources sidebar
		// $groups = craft()->sproutForms_groups->getAllFormGroups('id');
		// $forms = craft()->sproutForms_forms->getAllForms();

		// $noSources = array();
		// $prepSources = array();

		// foreach ($forms as $form) 
		// {
		// 	if ($form->groupId) 
		// 	{
		// 		if (!isset($prepSources[$form->groupId]['heading']))
		// 		{
		// 			$prepSources[$form->groupId]['heading'] = $groups[$form->groupId]->name;	
		// 		}
				
		// 		$prepSources[$form->groupId]['forms'][$form->id] = array(
		// 			'label' => $form->name,
		// 			'data' => array('formId' => $form->id),
		// 			'criteria' => array('formId' => $form->id)
		// 		);
		// 	}
		// 	else
		// 	{
		// 		$noSources[$form->id] = array(
		// 			'label' => $form->name,
		// 			'data' => array('formId' => $form->id),
		// 			'criteria' => array('formId' => $form->id)
		// 		);
		// 	}
		// }

		// usort($prepSources, 'self::_sortByGroupName');

		// // Build our sources for forms with no group
		// foreach ($noSources as $form) 
		// {
		// 	$sources[$form['data']['formId']] = array(
		// 		'label' => $form['label'],
		// 		'data' => array(
		// 			'formId' => $form['data']['formId'],
		// 		),
		// 		'criteria' => array(
		// 			'formId' => $form['criteria']['formId'],
		// 		)
		// 	);
		// }

		// // Build our sources sidebar for forms in groups
		// foreach ($prepSources as $source) 
		// {
		// 	$sources[] = array(
		// 		'heading' => $source['heading']
		// 	);

		// 	foreach ($source['forms'] as $form) 
		// 	{
		// 		$sources[] = array(
		// 			'label' => $form['label'],
		// 			'data' => array(
		// 				'formId' => $form['data']['formId'],
		// 			),
		// 			'criteria' => array(
		// 				'formId' => $form['criteria']['formId'],
		// 			)
		// 		);
		// 	}
		// }

		return $sources;
	}

	/**
	 * Returns the attributes that can be shown/sorted by in table views.
	 *
	 * @param string|null $source
	 * @return array
	 */
	public function defineTableAttributes($source = null)
	{
		return array(
			'title'        => Craft::t('Title'),
			'dateCreated'  => Craft::t('Date Created'),
			'dateUpdated'  => Craft::t('Date Updated'),
		);
	}

	/**
	 * Defines any custom element criteria attributes for this element type.
	 *
	 * @return array
	 */
	public function defineCriteriaAttributes()
	{
		return array(
			'title' => AttributeType::String,
		);
	}

	/**
	 * Defines which model attributes should be searchable.
	 *
	 * @return array
	 */
	public function defineSearchableAttributes()
	{
		return array(
			// 'emailBlastId', 
			'title',
		);
	}

	/**
	 * Modifies an element query targeting elements of this type.
	 *
	 * @param DbCommand $query
	 * @param ElementCriteriaModel $criteria
	 * @return mixed
	 */
	public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
	{
		$query
			->addSelect('emailblast.id AS emailBlastId')
			->join('sproutemail_emailblasts emailblast', 'emailblast.id = elements.id');
	}

	/**
	 * Populates an element model based on a query result.
	 *
	 * @param array $row
	 * @return array
	 */
	public function populateElementModel($row)
	{
		return SproutEmail_EmailBlastModel::populateModel($row);
	}
}
