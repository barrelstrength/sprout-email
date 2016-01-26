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
		return true;
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

	public function getSources($context = null)
	{
		// Grab all of our Notifications
		$notifications   = SproutEmail_CampaignRecord::model()->with('entries')->findAllByAttributes(array('type' => 'notification'));
		$notificationIds = array();

		$sources = array(
			'*' => array(
				'label' => Craft::t('All Sent Emails'),
			),
		);

		if (count($notifications))
		{
			// Create a list of Notification IDs we can use as criteria to filter by
			foreach ($notifications as $notification)
			{
				$notificationIds[] = $notification->entries[0]->id;
			}

			$sources['notifications'] = array(
				'label'    => Craft::t('Notifications'),
				'criteria' => array(
					'campaignEntryId' => $notificationIds
				)
			);
		}

		// Prepare the data for our sources sidebar
		$campaigns = SproutEmail_CampaignRecord::model()->with('entries')->findAllByAttributes(array('type' => 'email'));;

		if (count($campaigns))
		{
			$sources[] = array('heading' => Craft::t('Campaigns'));

			foreach ($campaigns as $campaign)
			{
				$key = 'campaign:'.$campaign->entries[0]->id;

				$sources[$key] = array(
					'label'    => $campaign->name,
					'data'     => array('campaignEntryId' => $campaign->id),
					'criteria' => array('campaignEntryId' => $campaign->id)
				);
			}
		}

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
				'emailSubject' => array('label' => Craft::t('Subject')),
				'fromEmail'    => array('label' => Craft::t('From Email')),
				'campaignNotificationId'    => array('label' => Craft::t('Notification Type')),
				'dateCreated'  => array('label' => Craft::t('Date Created')),
				'dateUpdated'  => array('label' => Craft::t('Date Updated'))
		);

		return $attributes;
	}

	public function defineSortableAttributes()
	{
		return array(
			'emailSubject' => Craft::t('Subject'),
			'dateCreated'  => Craft::t('Date Created')
		);
	}


	/**
	 * Returns default table columns for table views
	 *
	 * @return array
	 */
	public function getDefaultTableAttributes($source = null)
	{
		$attributes = array();

		$attributes[] = 'emailSubject';
		$attributes[] = 'fromEmail';
		$attributes[] = 'campaignNotificationId';
		$attributes[] = 'toEmail';
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
			'campaignNotificationId' => AttributeType::Number,
			'campaignEntryId' => AttributeType::Number,
			'emailSubject'    => AttributeType::String,
			'fromEmail'       => AttributeType::String,
			'toEmail'         => AttributeType::String,
			'dateCreated'     => AttributeType::Mixed
		);
	}


	public function getTableAttributeHtml(BaseElementModel $element, $attribute)
	{

		switch ($attribute) {

			default:
			{
				return parent::getTableAttributeHtml($element, $attribute);
			}
		}

	}

	public function getIndexHtml(
		$criteria,
		$disabledElementIds,
		$viewState,
		$sourceKey,
		$context,
		$includeContainer,
		$showCheckboxes
	) {

		$order = isset($viewState['order']) ? $viewState['order'] : 'dateCreated';
		$sort  = isset($viewState['sort']) ? $viewState['sort'] : 'desc';

		$criteria->limit = null;
		$criteria->order = sprintf('%s %s', $order, $sort);

		// Add this to prevent search error
		if (!empty($viewState['order']) && $viewState['order'] == 'score')
		{
			$criteria->order = 'score';
		}

		craft()->templates->includeJsResource('sproutemail/js/sproutmodal.js');
		craft()->templates->includeJs('var sproutModalInstance = new SproutModal(); sproutModalInstance.init();');

		return craft()->templates->render(
			'sproutemail/sentemails/_entryindex', array(
				'context'            => $context,
				'elementType'        => new ElementTypeVariable($this),
				'disabledElementIds' => $disabledElementIds,
				'elements'           => $criteria->find(),
			)
		);
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
		$query->addSelect('sentemail.*, sentemail.dateCreated')
			->join('sproutemail_sentemail sentemail', 'sentemail.id = elements.id');

		if ($criteria->campaignEntryId)
		{
			$query->andWhere(DbHelper::parseParam('sentemail.campaignEntryId', $criteria->campaignEntryId, $query->params));
		}

		if ($criteria->order)
		{
			// Trying to order by date creates ambiguity errors
			// Let's make sure mysql knows what we want to sort by
			if (stripos($criteria->order, 'elements.') === false)
			{
				$criteria->order = str_replace('dateCreated', 'sentemail.dateCreated', $criteria->order);
				$criteria->order = str_replace('dateUpdated', 'sentemail.dateUpdated', $criteria->order);
			}

		}

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
