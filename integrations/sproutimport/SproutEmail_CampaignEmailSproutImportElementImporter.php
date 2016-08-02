<?php
namespace Craft;

class SproutEmail_CampaignEmailSproutImportElementImporter extends BaseSproutImportElementImporter
{
	protected $campaignTypeModel = null;
	/**
	 * @return mixed
	 */
	public function getModelName()
	{
		return 'SproutEmail_CampaignEmail';
	}

	/**
	 * @return bool
	 */
	public function hasSeedGenerator()
	{
		return true;
	}

	public function setModel($model, $settings)
	{
		$model = parent::setModel($model, $settings);

		if (isset($settings['campaignId']))
		{
			$campaignTypeRecord = SproutEmail_CampaignTypeRecord::model()->findById($settings['campaignId']);

			$campaignTypeModel = SproutEmail_CampaignTypeModel::populateModel($campaignTypeRecord);

			$this->campaignTypeModel = $campaignTypeModel;
		}
		else
		{
			/*
			 * @todo Create a campaignTypeModel Setting Importer
			 */

			//$this->campaignTypeModel = new SproutEmail_CampaignTypeModel();
			//
			//$this->campaignTypeModel->mailer = "defaultmailer";
			//
			//$this->campaignTypeModel->setAttributes($settings);
			//
			//if (isset($settings['fieldLayout']) && !empty($settings['fieldLayout']))
			//{
			//	$fieldLayouts = array();
			//	foreach ($settings['fieldLayout'] as $name => $fields)
			//	{
			//		$entryFields = sproutImport()->getFieldIdsByHandle($name, $fields);
			//
			//		if (!empty($entryFields))
			//		{
			//			$fieldLayouts = array_merge($fieldLayouts, $entryFields);
			//		}
			//	}
			//
			//	$fieldLayout = craft()->fields->assembleLayout($fieldLayouts);
			//	$fieldLayout->type = 'SproutEmail_Campaign';
			//	$this->campaignTypeModel->setFieldLayout($fieldLayout);
			}

			$this->campaignTypeModel = sproutEmail()->campaignEmails->saveCampaignType($this->campaignTypeModel);
		}

		$model->campaignId = $this->campaignTypeModel->id;

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
		$campaignTypeModel = $this->campaignTypeModel;

		return sproutEmail()->campaignEmails->saveCampaignEmail($this->model, $campaignTypeModel);
	}

	public function defineKeys()
	{
		// This will accept all attributes for campaign model and fieldLayout to pass validation
		$campaignModel = new SproutEmail_CampaignEmailModel;

		$attributes = $campaignModel->defineAttributes();

		$attributeKeys = array_keys($attributes);

		return array_merge(array("fieldLayout", "campaignId"), $attributeKeys);
	}
}