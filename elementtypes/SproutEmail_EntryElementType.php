<?php
namespace Craft;

class SproutEmail_EntryElementType extends BaseElementType
{
	/**
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Sprout Email Entry');
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
			SproutEmail_EntryModel::ENABLED  => Craft::t('Enabled'),
			SproutEmail_EntryModel::PENDING  => Craft::t('Pending'),
			SproutEmail_EntryModel::DISABLED => Craft::t('Disabled')
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
		// Grab all of our Notifications
		$notifications = sproutEmail()->campaigns->getCampaigns('notification');
		$notificationIds = array();

		$sources = array(
			'*' => array(
				'label' => Craft::t('All Emails'),
			),
		);

		if (count($notifications))
		{
			// Create a list of Notification IDs we can use as criteria to filter by
			foreach ($notifications as $notification)
			{
				$notificationIds[] = $notification->id;
			}

			$sources['notifications'] = array(
				'label'    => Craft::t('Notifications'),
				'criteria' => array(
					'campaignId' => $notificationIds
				)
			);
		}

		// Prepare the data for our sources sidebar
		$campaigns = sproutEmail()->campaigns->getCampaigns('email');

		if (count($campaigns))
		{
			$sources[] = array('heading' => Craft::t('Campaigns'));

			foreach ($campaigns as $campaign)
			{
				$key = 'campaign:' . $campaign->id;

				$sources[$key] = array(
					'label'    => $campaign->name,
					'data'     => array('campaignId' => $campaign->id),
					'criteria' => array('campaignId' => $campaign->id)
				);
			}
		}

		return $sources;
	}

	/**
	 * @param ElementCriteriaModel $criteria
	 * @param array                $disabledElementIds
	 * @param array                $viewState
	 * @param null|string          $sourceKey
	 * @param null|string          $context
	 * @param                      $includeContainer
	 * @param                      $showCheckboxes
	 *
	 * @return string
	 */
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

		$order = isset($viewState['order']) ? $viewState['order'] : 'dateCreated';
		$sort = isset($viewState['sort']) ? $viewState['sort'] : 'desc';

		$criteria->limit = null;
		$criteria->order = sprintf('%s %s', $order, $sort);

		return craft()->templates->render(
			'sproutemail/entries/_entryindex', array(
				'context'            => $context,
				'elementType'        => new ElementTypeVariable($this),
				'disabledElementIds' => $disabledElementIds,
				'elements'           => $criteria->find(),
			)
		);
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
			'title'          => AttributeType::String,
			'campaignId'     => AttributeType::Number,
			'campaignHandle' => AttributeType::Handle,
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
			case SproutEmail_EntryModel::DISABLED:
			{
				$query->andWhere('elements.enabled = 0');

				break;
			}
			case SproutEmail_EntryModel::PENDING:
			{
				$query->andWhere('elements.enabled = 1');
				$query->andWhere('campaigns.template IS NULL OR campaigns.mailer IS NULL');

				break;
			}
			case SproutEmail_EntryModel::ARCHIVED:
			{
				$query->andWhere('elements.archived = 1');
				break;
			}
			case SproutEmail_EntryModel::READY:
			{
				$query->andWhere(
					'
					elements.enabled = 1
					AND campaigns.template IS NOT NULL
					AND campaigns.mailer IS NOT NULL'
				);

				break;
			}
			case SproutEmail_EntryModel::ENABLED:
			{
				$query->andWhere(
					'
					elements.enabled = 1
					AND campaigns.template IS NOT NULL
					AND campaigns.mailer IS NOT NULL'
				);

				break;
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
		$query
			->addSelect(
				'entries.id
				, entries.subjectLine as subjectLine
				, entries.campaignId as campaignId
				, entries.recipients as recipients
				, entries.fromName as fromName
				, entries.fromEmail as fromEmail
				, entries.replyToEmail as replyToEmail
				, entries.sent as sent
				, entries.enableFileAttachments as enableFileAttachments
				, campaigns.type as type'
			)
			->join('sproutemail_campaigns_entries entries', 'entries.id = elements.id')
			->join('sproutemail_campaigns campaigns', 'campaigns.id = entries.campaignId');

		if ($criteria->campaignId)
		{
			$query->andWhere(DbHelper::parseParam('entries.campaignId', $criteria->campaignId, $query->params));
		}

		if ($criteria->campaignHandle)
		{
			$query->andWhere(DbHelper::parseParam('campaigns.handle', $criteria->campaignHandle, $query->params));
		}
	}

	/**
	 * Gives us the ability to render campaign previews by using the Craft API and templates/render
	 *
	 * @param BaseElementModel $element
	 *
	 * @return array|bool|mixed
	 */
	public function routeRequestForMatchedElement(BaseElementModel $element)
	{
		// Only expose notification emails that have tokens
		if ($element->getType() == 'notification' && !craft()->request->getQuery(craft()->config->get('tokenParam')))
		{
			throw new HttpException(404);
		}

		$campaign = sproutEmail()->campaigns->getCampaignById($element->campaignId);

		if (!$campaign)
		{
			return false;
		}

		$extension = null;

		if (($type = craft()->request->getQuery('type')))
		{
			$extension = in_array(strtolower($type), array('txt', 'text')) ? '.txt' : null;
		}

		if (!craft()->templates->doesTemplateExist($campaign->template . $extension))
		{
			$templateName = $campaign->template . $extension;

			sproutEmail()->error(Craft::t("The template '{templateName}' could not be found", array(
				'templateName' => $templateName
			)));
		}

		$vars = array(
			'email'     => $element,
			'campaign'  => $campaign,
		  
		  // @deprecate in v3 in favor of the `email` variable
		  'entry'     => $element,
		);

		return array(
			'action' => 'templates/render',
			'params' => array(
				'template'  => $campaign->template . $extension,
				'variables' => $vars
			)
		);
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
		$deleteAction = craft()->elements->getAction('SproutEmail_Delete');

		$deleteAction->setParams(
			array(
				'confirmationMessage' => Craft::t('Are you sure you want to delete the selected emails?'),
				'successMessage'      => Craft::t('Emails deleted.'),
			)
		);

		$setStatusAction = craft()->elements->getAction('SproutEmail_SetStatus');

		return array($deleteAction, $setStatusAction);
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
		return SproutEmail_EntryModel::populateModel($row);
	}
}
