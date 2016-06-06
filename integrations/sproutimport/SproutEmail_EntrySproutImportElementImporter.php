<?php
namespace Craft;

class SproutEmail_EntrySproutImportElementImporter extends BaseSproutImportElementImporter
{
	/**
	 * @return mixed
	 */
	public function getModel()
	{
		$model = 'Craft\\SproutEmail_EntryModel';

		return new $model;
	}

	public function populateModel($model, $settings)
	{
		if ($settings['type'] == "notification")
		{
			$campaign = new SproutEmail_CampaignModel();

			$campaign->mailer = "defaultmailer";
		}
		elseif (isset($settings['campaignId']))
		{
			$campaign = sproutEmail()->campaigns->getCampaignById($settings['campaignId']);
		}
		else
		{
			throw new \Exception("Specify a campaign id.");
		}

		$campaign->setAttributes($settings);

		if (isset($settings['fieldLayout']) && !empty($settings['fieldLayout']))
		{
			foreach ($settings['fieldLayout'] as $name => $fields)
			{
				$entryFields = sproutImport()->getFieldIdsByHandle($name, $fields);

				if (!empty($entryFields))
				{
					$fieldLayout = craft()->fields->assembleLayout($entryFields);
					$fieldLayout->type = 'SproutEmail_Campaign';
					$campaign->setFieldLayout($fieldLayout);
				}
			}
		}

		$this->campaignModel  = sproutEmail()->campaigns->saveCampaign($campaign);

		$model->campaignId = $this->campaignModel->id;

		$this->model = $model;
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

		return array_merge(array("fieldLayout"), $attributeKeys);
	}
}