<?php
namespace Craft;

class SproutEmail_NotificationEmailElementType extends BaseElementType
{
	/**
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Sprout Notification Email');
	}

	/**
	 * @return bool
	 */
	public function hasTitles()
	{
		return true;
	}

	/**
	 * @return bool
	 */
	public function hasContent()
	{
		return true;
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
		return true;
	}

	public function getStatuses()
	{
		return array(
			SproutEmail_CampaignEmailModel::ENABLED  => Craft::t('Enabled'),
			SproutEmail_CampaignEmailModel::PENDING  => Craft::t('Pending'),
			SproutEmail_CampaignEmailModel::DISABLED => Craft::t('Disabled')
		);
	}

	/**
	 * Returns this element type's sources.
	 *
	 * @param string|null $context
	 *
	 * @return array|false
	 */
	public function getSources($context = null)
	{
		$sources = array(
			'*' => array(
				'label' => Craft::t('All Notifications'),
			),
		);

		return $sources;
	}


	/**
	 * Returns the attributes that can be selected as table columns
	 *
	 * @return array
	 */
	public function defineAvailableTableAttributes()
	{
		$attributes = array(
			'title'       => array('label' => Craft::t('Title')),
			'dateCreated' => array('label' => Craft::t('Date Created')),
			'dateUpdated' => array('label' => Craft::t('Date Updated')),
			'send'        => array('label' => Craft::t('Send'))
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

		$attributes[] = 'title';
		$attributes[] = 'dateCreated';
		$attributes[] = 'dateUpdated';
		$attributes[] = 'send';

		return $attributes;
	}

	/**
	 * @inheritDoc IElementType::getTableAttributeHtml()
	 *
	 * @param BaseElementModel $element
	 * @param string           $attribute
	 *
	 * @return string
	 */
	public function getTableAttributeHtml(BaseElementModel $element, $attribute)
	{
		if ($attribute == 'send')
		{
			return craft()->templates->render('sproutemail/notifications/_modals/modalLink', array(
				'notification' => $element
			));
		}

		return parent::getTableAttributeHtml($element, $attribute);
	}

	public function getIndexHtml(
		$criteria,
		$disabledElementIds,
		$viewState,
		$sourceKey,
		$context,
		$includeContainer,
		$showCheckboxes
	)
	{
		craft()->templates->includeJsResource('sproutemail/js/sproutmodal.js');
		craft()->templates->includeJs('var sproutModalInstance = new SproutModal(); sproutModalInstance.init();');

		sproutEmail()->mailers->includeMailerModalResources();

		craft()->templates->includeCssResource('sproutemail/css/sproutemail.css');

		return parent::getIndexHtml(
			$criteria,
			$disabledElementIds,
			$viewState,
			$sourceKey,
			$context,
			$includeContainer,
			$showCheckboxes
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
			'title'          => AttributeType::String
		);
	}


	/**
	 * Defines which model attributes should be searchable.
	 *
	 * @return array
	 */
	public function defineSearchableAttributes()
	{
		return array('title');
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
		$query->addSelect('notificationemail.*')
			->join('sproutemail_notifications notificationemail', 'notificationemail.id = elements.id');

		if ($criteria->order)
		{
			// Trying to order by date creates ambiguity errors
			// Let's make sure mysql knows what we want to sort by
			if (stripos($criteria->order, 'elements.') === false)
			{
				$criteria->order = str_replace('dateCreated', 'notificationemail.dateCreated', $criteria->order);
				$criteria->order = str_replace('dateUpdated', 'notificationemail.dateUpdated', $criteria->order);
			}
		}
	}

	/**
	 * @inheritDoc IElementType::getAvailableActions()
	 *
	 * @param string|null $source
	 *
	 * @return array|null
	 */
	public function getAvailableActions($source = null)
	{
		$deleteAction = craft()->elements->getAction('SproutEmail_NotificationDelete');

		$deleteAction->setParams(
			array(
				'confirmationMessage' => Craft::t('Are you sure you want to delete the selected emails?'),
				'successMessage'      => Craft::t('Emails deleted.'),
			)
		);

		$setStatusAction = craft()->elements->getAction('SproutEmail_SetStatus');

		//return array($deleteAction, $setStatusAction);
		return array($deleteAction);
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
		return SproutEmail_NotificationEmailModel::populateModel($row);
	}
}
