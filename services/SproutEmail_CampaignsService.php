<?php
namespace Craft;

class SproutEmail_CampaignsService extends BaseApplicationComponent
{
	/**
	 * @param string|null $type
	 *
	 * @return SproutEmail_CampaignModel[]
	 */
	public function getCampaigns($type = null)
	{
		if ($type)
		{
			$campaigns = SproutEmail_CampaignRecord::model()->findAllByAttributes(array('type' => $type));
		}
		else
		{
			$campaigns = SproutEmail_CampaignRecord::model()->findAll();
		}

		if ($campaigns)
		{
			return SproutEmail_CampaignModel::populateModels($campaigns);
		}
	}

	/**
	 * @param $campaignId
	 *
	 * @return SproutEmail_CampaignModel
	 */
	public function getCampaignById($campaignId)
	{
		$campaignRecord = SproutEmail_CampaignRecord::model();
		$campaignRecord = $campaignRecord->with('entries')->findById($campaignId);

		if ($campaignRecord)
		{
			return SproutEmail_CampaignModel::populateModel($campaignRecord);
		}
		else
		{
			return new SproutEmail_CampaignModel();
		}
	}

	public function getCampaignByEntryAndCampaignId($entryId = false, $campaignId = false)
	{
		return SproutEmail_CampaignRecord::model()->getCampaignByEntryAndCampaignId($entryId, $campaignId);
	}

	/**
	 * @param SproutEmail_CampaignModel $campaign
	 * @param string                    $tab
	 *
	 * @throws \Exception
	 * @return int CampaignRecordId
	 */
	public function saveCampaign(SproutEmail_CampaignModel $campaign)
	{
		$campaignRecord = new SproutEmail_CampaignRecord();
		$oldCampaign = null;

		if (is_numeric($campaign->id) && !$campaign->saveAsNew)
		{
			$campaignRecord = SproutEmail_CampaignRecord::model()->findById($campaign->id);
			$oldCampaign = SproutEmail_CampaignModel::populateModel($campaignRecord);
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
		$campaign->fieldLayoutId = $fieldLayout->id;
		$campaignRecord->fieldLayoutId = $fieldLayout->id;

		$campaignRecord = $this->saveCampaignInfo($campaign);

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
			$criteria = craft()->elements->getCriteria('SproutEmail_Entry');
			$criteria->campaignId = $campaign->id;

			$entryIds = $criteria->ids();

			if ($entryIds != null)
			{
				foreach ($entryIds as $entryId)
				{
					craft()->config->maxPowerCaptain();

					$criteria = craft()->elements->getCriteria('SproutEmail_Entry');
					$criteria->id     = $entryId;
					$criteria->locale = "en_us";
					$criteria->status = null;
					$updateEntry = $criteria->first();

					// @todo replace the getContent()->id check with 'strictLocale' param once it's added
					if ($updateEntry && $updateEntry->getContent()->id) {
						craft()->elements->updateElementSlugAndUri($updateEntry, false, false);
					}
				}
			}
		}

		if ($transaction)
		{
			$transaction->commit();
		}

		return SproutEmail_CampaignModel::populateModel($campaignRecord);
	}

	protected function saveCampaignInfo(SproutEmail_CampaignModel &$campaign)
	{
		$oldCampaignMailer = null;
		$campaignRecord = new SproutEmail_CampaignRecord();

		if (isset($campaign->id) && is_numeric($campaign->id) && !$campaign->saveAsNew)
		{
			$campaignRecord = SproutEmail_CampaignRecord::model()->findById($campaign->id);

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
		$campaignRecord->fieldLayoutId = $campaign->fieldLayoutId;
		$campaignRecord->name = $campaign->name;
		$campaignRecord->handle = $campaign->handle;
		$campaignRecord->type = $campaign->type;
		$campaignRecord->titleFormat = $campaign->titleFormat;
		$campaignRecord->hasUrls = $campaign->hasUrls;
		$campaignRecord->hasAdvancedTitles = $campaign->hasAdvancedTitles;
		$campaignRecord->mailer = $campaign->mailer;

		$campaignRecord->urlFormat = $campaign->urlFormat;
		$campaignRecord->template = $campaign->template;
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
	public function deleteCampaign($campaignId)
	{
		try
		{
			craft()->db->createCommand()->delete('sproutemail_campaigns', array(
				'id' => $campaignId
			));

			// This only applies to Notifications
			sproutEmail()->notifications->deleteNotificationsByCampaignId($campaignId);

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
	 * @return SproutEmail_CampaignRecord
	 */
	public function getFieldValue($field, $value)
	{
		$criteria = new \CDbCriteria();
		$criteria->condition = "{$field} =:value";
		$criteria->params = array(':value' => $value);
		$criteria->limit = 1;

		$result = SproutEmail_CampaignRecord::model()->find($criteria);

		return $result;
	}

	/**  Get campaign Model by the related entry id
	 *
	 * @param $entryId
	 *
	 * @return bool|SproutEmail_CampaignModel
	 */
	public function getCampaignByEntryId($entryId)
	{
		$entry = SproutEmail_EntryRecord::model()->findById($entryId);
		if (!isset($entry->campaignId))
		{
			return false;
		}

		$campaignId = $entry->campaignId;

		$campaign = $this->getCampaignById($campaignId);
		if ($campaign->id != null)
		{
			return $campaign;
		}

		return false;
	}
}
