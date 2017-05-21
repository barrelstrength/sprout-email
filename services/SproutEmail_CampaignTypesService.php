<?php

namespace Craft;

class SproutEmail_CampaignTypesService extends BaseApplicationComponent
{
	/**
	 * @param string|null $type
	 *
	 * @return SproutEmail_CampaignTypeModel[]
	 */
	public function getCampaignTypes()
	{
		$campaignsType = SproutEmail_CampaignTypeRecord::model()->findAll();

		if ($campaignsType)
		{
			return SproutEmail_CampaignTypeModel::populateModels($campaignsType);
		}
	}

	/**
	 * @param $campaignTypeId
	 *
	 * @return SproutEmail_CampaignTypeModel
	 */
	public function getCampaignTypeById($campaignTypeId)
	{
		$campaignRecord = SproutEmail_CampaignTypeRecord::model();
		$campaignRecord = $campaignRecord->with('campaignEmails')->findById($campaignTypeId);

		if ($campaignRecord)
		{
			return SproutEmail_CampaignTypeModel::populateModel($campaignRecord);
		}
		else
		{
			return new SproutEmail_CampaignTypeModel();
		}
	}

	/**
	 * @param SproutEmail_CampaignTypeModel $campaignType
	 *
	 * @return int
	 * @internal param string $tab
	 *
	 */
	public function saveCampaignType(SproutEmail_CampaignTypeModel $campaignType)
	{
		$campaignTypeRecord = new SproutEmail_CampaignTypeRecord();
		$oldCampaignType    = null;

		if (is_numeric($campaignType->id) && !$campaignType->saveAsNew)
		{
			$campaignTypeRecord = SproutEmail_CampaignTypeRecord::model()->findById($campaignType->id);
			$oldCampaignType    = SproutEmail_CampaignTypeModel::populateModel($campaignTypeRecord);
		}

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

		$fieldLayout = $campaignType->getFieldLayout();
		craft()->fields->saveLayout($fieldLayout);

		// Delete our previous record
		if ($campaignType->id && $oldCampaignType && $oldCampaignType->fieldLayoutId)
		{
			craft()->fields->deleteLayoutById($oldCampaignType->fieldLayoutId);
		}

		// Assign our new layout id info to our
		// form model and records
		$campaignType->fieldLayoutId       = $fieldLayout->id;
		$campaignTypeRecord->fieldLayoutId = $fieldLayout->id;

		$campaignTypeRecord = $this->saveCampaignTypeInfo($campaignType);

		if ($campaignTypeRecord->hasErrors())
		{
			if ($transaction)
			{
				$transaction->rollBack();
			}

			return false;
		}
		else
		{
			$criteria                 = craft()->elements->getCriteria('SproutEmail_CampaignEmail');
			$criteria->campaignTypeId = $campaignType->id;
			$criteria->localeEnabled  = null;
			$criteria->status         = null;
			$criteria->limit          = null;

			craft()->tasks->createTask('ResaveElements', Craft::t('Re-saving Campaign Emails'), array(
				'elementType' => 'SproutEmail_CampaignEmail',
				'criteria'    => $criteria->getAttributes()
			));
		}

		if ($transaction)
		{
			$transaction->commit();

			// Pass update attributes
			$campaignType->setAttributes($campaignTypeRecord->getAttributes());
		}

		return SproutEmail_CampaignTypeModel::populateModel($campaignTypeRecord);
	}

	protected function saveCampaignTypeInfo(SproutEmail_CampaignTypeModel &$campaignType)
	{
		$oldCampaignMailer  = null;
		$campaignTypeRecord = new SproutEmail_CampaignTypeRecord();

		if (isset($campaignType->id) && is_numeric($campaignType->id) && !$campaignType->saveAsNew)
		{
			$campaignTypeRecord = SproutEmail_CampaignTypeRecord::model()->findById($campaignType->id);

			if (!$campaignTypeRecord)
			{
				throw new Exception(
					Craft::t(
						'No campaign exists with the ID “{id}”', array(
							'id' => $campaignType->id
						)
					)
				);
			}
		}

		// Set common attributes
		$campaignTypeRecord->fieldLayoutId     = $campaignType->fieldLayoutId;
		$campaignTypeRecord->name              = $campaignType->name;
		$campaignTypeRecord->handle            = $campaignType->handle;
		$campaignTypeRecord->titleFormat       = $campaignType->titleFormat;
		$campaignTypeRecord->hasUrls           = $campaignType->hasUrls;
		$campaignTypeRecord->hasAdvancedTitles = $campaignType->hasAdvancedTitles;
		$campaignTypeRecord->mailer            = $campaignType->mailer;

		$campaignTypeRecord->urlFormat         = $campaignType->urlFormat;
		$campaignTypeRecord->template          = $campaignType->template;
		$campaignTypeRecord->templateCopyPaste = $campaignType->templateCopyPaste;

		if ($campaignType->saveAsNew)
		{
			$campaignTypeRecord->handle = $campaignType->handle . "-1";
		}

		$campaignTypeRecord->validate();
		$campaignType->addErrors($campaignTypeRecord->getErrors());

		// Get the title back from model because record validate append it with 1
		if ($campaignType->saveAsNew)
		{
			$campaignTypeRecord->name = $campaignType->name;
		}

		if (!$campaignTypeRecord->hasErrors())
		{
			$campaignTypeRecord->save(false);
		}

		return $campaignTypeRecord;
	}

	/**
	 * Deletes a campaign by its ID along with associations;
	 * also cleans up any remaining orphans
	 *
	 * @param int $campaignTypeId
	 *
	 * @return bool
	 */
	public function deleteCampaignType($campaignTypeId)
	{
		try
		{
			craft()->db->createCommand()->delete('sproutemail_campaigntype', array(
				'id' => $campaignTypeId
			));

			return true;
		}
		catch (\Exception $e)
		{
			return false;
		}
	}

	/**
	 * Returns the value of a given field
	 *
	 * @param string $field
	 * @param string $value
	 *
	 * @return SproutEmail_CampaignTypeRecord
	 */
	public function getFieldValue($field, $value)
	{
		$criteria            = new \CDbCriteria();
		$criteria->condition = "{$field} =:value";
		$criteria->params    = array(':value' => $value);
		$criteria->limit     = 1;

		$result = SproutEmail_CampaignTypeRecord::model()->find($criteria);

		return $result;
	}

	public function getCampaignTypesByHandle($handle)
	{
		$campaignTypeRecords = SproutEmail_CampaignTypeRecord::model()->findAllByAttributes(array(
			'handle' => $handle
		));

		return SproutEmail_CampaignTypeModel::populateModels($campaignTypeRecords);
	}
}
