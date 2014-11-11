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
	 * @inheritDoc IElementType::hasStatuses()
	 *
	 * @return bool
	 */
	public function hasStatuses()
	{
		return true;
	}

	public function getStatuses()
	{
		return array(
			SproutEmail_EmailBlastModel::READY     => Craft::t('Ready'),
			SproutEmail_EmailBlastModel::PENDING   => Craft::t('Pending'),
			SproutEmail_EmailBlastModel::DISABLED  => Craft::t('Disabled'),
			SproutEmail_EmailBlastModel::ARCHIVED  => Craft::t('Archived'),
		);
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
				'label'    => Craft::t('All Emails'),
			)
		);

		// Prepare the data for our sources sidebar
		$emailBlastTypes = craft()->sproutEmail_emailBlastType->getEmailBlastTypes('blast');

		$sources[] = array('heading' => 'Campaigns');

		foreach ($emailBlastTypes as $emailBlastType) 
		{	
			$key = 'emailBlastType:'.$emailBlastType->id;
			
			$sources[$key] = array(
				'label' => $emailBlastType->name,
				'data' => array('emailBlastTypeId' => $emailBlastType->id),
				'criteria' => array('emailBlastTypeId' => $emailBlastType->id)
			);
		}

		$sources[] = array('heading' => 'Autoresponders');

		$sources['notifications'] = array(
			'label' => Craft::t('List of Notifications')
		); 

		return $sources;
	}

	public function getIndexHtml($criteria, $disabledElementIds, $viewState, $sourceKey, $context)
	{
		if ($context == 'index')
		{
			$criteria->offset = 0;
			$criteria->limit = null;

			$source = $this->getSource($sourceKey, $context);

			return craft()->templates->render('sproutemail/emailblasts/_emailblastindex', array(
				'context'             => $context,
				'elementType'         => new ElementTypeVariable($this),
				'disabledElementIds'  => $disabledElementIds,
				'elements'            => $criteria->find(),
			));
		}
		else
		{
			return parent::getIndexHtml($criteria, $disabledElementIds, $viewState, $sourceKey, $context);
		}
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
			'subjectLine' => AttributeType::String,
			'emailBlastTypeId' => AttributeType::Number,
		);
	}

	/**
	 * @inheritDoc IElementType::getElementQueryStatusCondition()
	 *
	 * @param DbCommand $query
	 * @param string    $status
	 *
	 * @return array|false|string|void
	 */
	public function getElementQueryStatusCondition(DbCommand $query, $status)
	{
		switch ($status)
		{
			case SproutEmail_EmailBlastModel::DISABLED:
			{
				return 'emailblasttypes.template IS NULL';
			}

			case SproutEmail_EmailBlastModel::PENDING:
			{
				return array('and',
					'elements.enabled = 0',
					'emailblasttypes.template IS NOT NULL',
					'emailblasts.sent = 0',
				);
			}

			case SproutEmail_EmailBlastModel::READY:
			{
				return array('and',
					'elements.enabled = 1',
					'elements_i18n.enabled = 1',
					'emailblasttypes.template IS NOT NULL',
					'emailblasts.sent = 0',
				);
			}

			case SproutEmail_EmailBlastModel::ARCHIVED:
			{
				return 'emailblasts.sent = 1';
			}
		}
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
			->addSelect('emailblasts.id AS emailBlastId, 
									 emailblasts.emailBlastTypeId AS emailBlastTypeId,
									 emailblasts.subjectLine AS subjectLine, 
									 emailblasts.sent AS sent
				')
			->join('sproutemail_emailblasts emailblasts', 'emailblasts.id = elements.id')
			->join('sproutemail_emailblasttypes emailblasttypes', 'emailblasttypes.id = emailblasts.emailBlastTypeId');

		if ($criteria->emailBlastTypeId) 
		{
			$query->andWhere(DbHelper::parseParam('emailblasts.emailBlastTypeId', $criteria->emailBlastTypeId, $query->params));
		}
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
