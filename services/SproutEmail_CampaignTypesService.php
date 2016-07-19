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
		$campaigns = SproutEmail_CampaignTypeRecord::model()->findAll();

		if ($campaigns)
		{
			return SproutEmail_CampaignTypeModel::populateModels($campaigns);
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
		$campaignRecord = $campaignRecord->with('entries')->findById($campaignTypeId);

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
	 * @param SproutEmail_CampaignTypeModel $campaign
	 * @param string                        $tab
	 *
	 * @throws \Exception
	 * @return int CampaignRecordId
	 */
	public function saveCampaignType(SproutEmail_CampaignTypeModel $campaign)
	{
		$campaignRecord = new SproutEmail_CampaignTypeRecord();
		$oldCampaign    = null;

		if (is_numeric($campaign->id) && !$campaign->saveAsNew)
		{
			$campaignRecord = SproutEmail_CampaignTypeRecord::model()->findById($campaign->id);
			$oldCampaign    = SproutEmail_CampaignTypeModel::populateModel($campaignRecord);
		}

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

		$fieldLayout = $campaign->getFieldLayout();
		craft()->fields->saveLayout($fieldLayout);

		// Delete our previous record
		if ($campaign->id && $oldCampaign && $oldCampaign->fieldLayoutId)
		{
			craft()->fields->deleteLayoutById($oldCampaign->fieldLayoutId);
		}

		// Assign our new layout id info to our
		// form model and records
		$campaign->fieldLayoutId       = $fieldLayout->id;
		$campaignRecord->fieldLayoutId = $fieldLayout->id;

		$campaignRecord = $this->saveCampaignTypeInfo($campaign);

		if ($campaignRecord->hasErrors())
		{
			if ($transaction)
			{
				$transaction->rollBack();
			}

			return $campaign;
		}
		else
		{
			// Updates element i18n slug and uri
			$criteria             = craft()->elements->getCriteria('SproutEmail_CampaignEmail');
			$criteria->campaignId = $campaign->id;

			$emailIds = $criteria->ids();

			if ($emailIds != null)
			{
				foreach ($emailIds as $emailId)
				{
					craft()->config->maxPowerCaptain();

					$criteria         = craft()->elements->getCriteria('SproutEmail_CampaignEmail');
					$criteria->id     = $emailId;
					$criteria->locale = "en_us";
					$criteria->status = null;
					$updateEntry      = $criteria->first();

					// @todo replace the getContent()->id check with 'strictLocale' param once it's added
					if ($updateEntry && $updateEntry->getContent()->id)
					{
						craft()->elements->updateElementSlugAndUri($updateEntry, false, false);
					}
				}
			}
		}

		if ($transaction)
		{
			$transaction->commit();
		}

		return SproutEmail_CampaignTypeModel::populateModel($campaignRecord);
	}

	protected function saveCampaignTypeInfo(SproutEmail_CampaignTypeModel &$campaign)
	{
		$oldCampaignMailer = null;
		$campaignRecord    = new SproutEmail_CampaignTypeRecord();

		if (isset($campaign->id) && is_numeric($campaign->id) && !$campaign->saveAsNew)
		{
			$campaignRecord = SproutEmail_CampaignTypeRecord::model()->findById($campaign->id);

			if (!$campaignRecord)
			{
				throw new Exception(
					Craft::t(
						'No campaign exists with the ID “{id}”', array(
							'id' => $campaign->id
						)
					)
				);
			}

			$oldCampaignMailer = $campaignRecord->mailer;
		}

		// Set common attributes
		$campaignRecord->fieldLayoutId     = $campaign->fieldLayoutId;
		$campaignRecord->name              = $campaign->name;
		$campaignRecord->handle            = $campaign->handle;
		$campaignRecord->titleFormat       = $campaign->titleFormat;
		$campaignRecord->hasUrls           = $campaign->hasUrls;
		$campaignRecord->hasAdvancedTitles = $campaign->hasAdvancedTitles;
		$campaignRecord->mailer            = $campaign->mailer;

		$campaignRecord->urlFormat         = $campaign->urlFormat;
		$campaignRecord->template          = $campaign->template;
		$campaignRecord->templateCopyPaste = $campaign->templateCopyPaste;

		if ($campaign->saveAsNew)
		{
			$campaignRecord->handle = $campaign->handle . "-1";
		}

		$campaignRecord->validate();
		$campaign->addErrors($campaignRecord->getErrors());

		// Get the title back from model because record validate append it with 1
		if ($campaign->saveAsNew)
		{
			$campaignRecord->name = $campaign->name;
		}

		if (!$campaignRecord->hasErrors())
		{
			$campaignRecord->save(false);
		}

		return $campaignRecord;
	}

	/**
	 * Deletes a campaign by its ID along with associations;
	 * also cleans up any remaining orphans
	 *
	 * @param int $campaignId
	 *
	 * @return bool
	 */
	public function deleteCampaignType($campaignId)
	{
		try
		{
			craft()->db->createCommand()->delete('sproutemail_campaigntype', array(
				'id' => $campaignId
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
}
