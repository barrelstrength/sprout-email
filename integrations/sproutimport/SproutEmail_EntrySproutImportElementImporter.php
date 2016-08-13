<?php
namespace Craft;

class SproutEmail_EntrySproutImportElementImporter extends BaseSproutImportElementImporter
{
	/**
	 * @return mixed
	 */
	public function getModelName()
	{
		return 'SproutEmail_EntryModel';
	}

	public function populateModel($model, $settings)
	{
		$model = parent::populateModel($model, $settings);

		if (isset($settings['campaignId']))
		{
			$this->campaignModel = sproutEmail()->campaigns->getCampaignById($settings['campaignId']);
		}
		else
		{
			$campaign = new SproutEmail_CampaignModel();

			if (isset($settings['type']) && $settings['type'] == "notification")
			{
				$campaign->mailer = "defaultmailer";
			}

			$campaign->setAttributes($settings);

			if (isset($settings['fieldLayout']) && !empty($settings['fieldLayout']))
			{
				$fieldLayouts = array();
				foreach ($settings['fieldLayout'] as $name => $fields)
				{
					$entryFields = sproutImport()->getFieldIdsByHandle($name, $fields);

					if (!empty($entryFields))
					{
						$fieldLayouts = array_merge($fieldLayouts, $entryFields);
					}
				}

				$fieldLayout       = craft()->fields->assembleLayout($fieldLayouts);
				$fieldLayout->type = 'SproutEmail_Campaign';
				$campaign->setFieldLayout($fieldLayout);
			}

			$this->campaignModel = sproutEmail()->campaigns->saveCampaign($campaign);
		}

		$model->campaignId = $this->campaignModel->id;

		$this->model = $model;

		return $this->model;
	}

	/**
	 * @return bool
	 * @throws Exception
	 * @throws \Exception
	 */
	public function save()
	{
		$campaign = $this->campaignModel;

		return sproutEmail()->entries->saveEntry($this->model, $campaign);
	}

	public function defineKeys()
	{
		// This will accept all attributes for campaign model and fieldLayout to pass validation
		$campaigModel = new SproutEmail_CampaignModel;

		$attributes = $campaigModel->defineAttributes();

		$attributeKeys = array_keys($attributes);

		return array_merge(array("fieldLayout", "campaignId"), $attributeKeys);
	}
}