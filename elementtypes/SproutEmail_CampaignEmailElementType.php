<?php
namespace Craft;

class SproutEmail_CampaignEmailElementType extends BaseElementType
{
	/**
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Sprout Email Campaign Emails');
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
			SproutEmail_CampaignEmailModel::ARCHIVED => Craft::t('Sent'),
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
				'label' => Craft::t('All Campaigns'),
			),
		);

		$campaignTypes = sproutEmail()->campaignTypes->getCampaignTypes();

		if (count($campaignTypes))
		{
			$sources[] = array('heading' => Craft::t('Campaigns'));

			foreach ($campaignTypes as $campaignType)
			{
				$key = 'campaignType:' . $campaignType->id;

				$sources[$key] = array(
					'label'    => $campaignType->name,
					'data'     => array('campaignTypeId' => $campaignType->id),
					'criteria' => array('campaignTypeId' => $campaignType->id)
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
	public function getIndexHtml($criteria, $disabledElementIds, $viewState, $sourceKey, $context, $includeContainer, $showCheckboxes)
	{
		craft()->templates->includeJsResource('sproutemail/js/sproutmodal.js');
		craft()->templates->includeJs('var sproutModalInstance = new SproutModal(); sproutModalInstance.init();');

		sproutEmail()->mailers->includeMailerModalResources();

		$order = isset($viewState['order']) ? $viewState['order'] : 'dateCreated';
		$sort  = isset($viewState['sort']) ? $viewState['sort'] : 'desc';

		$criteria->limit = null;
		$criteria->order = sprintf('%s %s', $order, $sort);

		return parent::getIndexHtml($criteria, $disabledElementIds, $viewState, $sourceKey, $context, $includeContainer, $showCheckboxes);
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
		$campaignType = sproutEmail()->campaignTypes->getCampaignTypeById($element->campaignTypeId);

		if ($attribute == 'send')
		{
			$mailer       = sproutEmail()->mailers->getMailerByName($campaignType->mailer);

			return craft()->templates->render('sproutemail/_partials/campaigns/prepareLink', array(
				'email'        => $element,
				'campaignType' => $campaignType,
				'mailer'       => $mailer
			));
		}

		if ($attribute == 'preview')
		{
			return craft()->templates->render('sproutemail/_partials/campaigns/previewLinks', array(
				'email'        => $element,
				'campaignType' => $campaignType
			));
		}

		return parent::getTableAttributeHtml($element, $attribute);
	}

	/**
	 * Returns the attributes that can be selected as table columns
	 *
	 * @return array
	 */
	public function defineAvailableTableAttributes()
	{
		$attributes = array(
			'title'        => array('label' => Craft::t('Title')),
			'subjectLine'  => array('label' => Craft::t('Subject')),
			'dateCreated'  => array('label' => Craft::t('Date Created')),
			'lastDateSent' => array('label' => Craft::t('Last Date Sent')),
			'dateUpdated'  => array('label' => Craft::t('Date Updated')),
			'preview'      => array('label' => Craft::t('Preview')),
			'send'         => array('label' => Craft::t('Send'))
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
		$attributes[] = 'lastDateSent';
		$attributes[] = 'dateCreated';
		$attributes[] = 'dateUpdated';
		$attributes[] = 'preview';
		$attributes[] = 'send';

		return $attributes;
	}

	public function defineSortableAttributes()
	{
		$attributes['title']       = Craft::t('Subject Line');
		$attributes['dateCreated'] = Craft::t('Date Created');
		$attributes['dateUpdated'] = Craft::t('Date Updated');

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
			'campaignTypeId' => AttributeType::Number,
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
			case SproutEmail_CampaignEmailModel::DISABLED:
			{
				$query->andWhere('elements.enabled = 0');

				break;
			}
			case SproutEmail_CampaignEmailModel::PENDING:
			{
				$query->andWhere('elements.enabled = 1');
				$query->andWhere('campaigntype.template IS NULL OR campaigntype.mailer IS NULL');

				break;
			}
			case SproutEmail_CampaignEmailModel::ARCHIVED:
			{
				$query->andWhere('elements.archived = 1');


				break;
			}
			case SproutEmail_CampaignEmailModel::READY:
			{
				$query->andWhere(
					'
					elements.enabled = 1
					AND campaigntype.template IS NOT NULL
					AND campaigntype.mailer IS NOT NULL'
				);

				break;
			}
			case SproutEmail_CampaignEmailModel::ENABLED:
			{
				$query->andWhere(
					'
					elements.enabled = 1
					AND campaigntype.template IS NOT NULL
					AND campaigntype.mailer IS NOT NULL'
				);
				$query->andWhere('elements.archived = 0');
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
				'campaigns.id, 
				 campaigns.subjectLine as subjectLine,
				 campaigns.campaignTypeId as campaignTypeId,
				 campaigns.recipients as recipients,
				 campaigns.fromName as fromName,
				 campaigns.fromEmail as fromEmail,
				 campaigns.replyToEmail as replyToEmail,
				 campaigns.sent as sent,
				 campaigns.lastDateSent as lastDateSent,
				 campaigns.enableFileAttachments as enableFileAttachments'
			)
			->join('sproutemail_campaignemails campaigns', 'campaigns.id = elements.id')
			->join('sproutemail_campaigntype campaigntype', 'campaigntype.id = campaigns.campaignTypeId');

		// This condition will allow archive status to be displayed initially and able to filter with proper entries
		if ($criteria->status == SproutEmail_CampaignEmailModel::ARCHIVED)
		{
			$query->where('elements.archived = 1');
		}
		elseif ($criteria->status == null)
		{
			$query->orWhere('elements.archived = 1');
		}


		if ($criteria->campaignTypeId)
		{
			$query->andWhere(DbHelper::parseParam('campaigns.campaignTypeId', $criteria->campaignTypeId, $query->params));
		}

		if ($criteria->campaignHandle)
		{
			$query->andWhere(DbHelper::parseParam('campaigntype.handle', $criteria->campaignHandle, $query->params));
		}
	}

	/**
	 * Gives us the ability to render campaign previews by using the Craft API and templates/render
	 *
	 * @param BaseElementModel $element
	 *
	 * @return array|bool
	 * @throws HttpException
	 */
	public function routeRequestForMatchedElement(BaseElementModel $element)
	{
		$campaignType = sproutEmail()->campaignTypes->getCampaignTypeById($element->campaignTypeId);

		if (!$campaignType)
		{
			return false;
		}

		$extension = null;

		if (($type = craft()->request->getQuery('type')))
		{
			$extension = in_array(strtolower($type), array('txt', 'text')) ? '.txt' : null;
		}

		if (!craft()->templates->doesTemplateExist($campaignType->template . $extension))
		{
			$templateName = $campaignType->template . $extension;

			sproutEmail()->error(Craft::t("The template '{templateName}' could not be found", array(
				'templateName' => $templateName
			)));
		}

		return array(
			'action' => 'templates/render',
			'params' => array(
				'template'  => $campaignType->template . $extension,
				'variables' => array(
					'email'        => $element,
					'campaignType' => $campaignType,

					// @deprecate in v3 `entry` in favor of the `email` variable
					// @deprecate in v3 `campaign` in favor of the `campaignType` variable
					'entry'        => $element,
					'campaign'     => $campaignType,
				)
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
		$deleteAction = craft()->elements->getAction('SproutEmail_CampaignEmailDelete');

		$deleteAction->setParams(array(
			'confirmationMessage' => Craft::t('Are you sure you want to delete the selected emails?'),
			'successMessage'      => Craft::t('Emails deleted.'),
		));

		$setStatusAction = craft()->elements->getAction('SproutEmail_SetStatus');

		return array($deleteAction, $setStatusAction);
	}

	/**
	 * Populates an element model based on a query result.
	 *
	 * @param array $row
	 *
	 * @return BaseModel
	 */
	public function populateElementModel($row)
	{
		return SproutEmail_CampaignEmailModel::populateModel($row);
	}
}
