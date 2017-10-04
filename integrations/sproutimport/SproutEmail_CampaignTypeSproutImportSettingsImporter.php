<?php
namespace Craft;

class SproutEmail_CampaignTypeSproutImportSettingsImporter extends BaseSproutImportSettingsImporter
{

	public function getName()
	{
		return "Sprout Email Campaign Type";
	}

	/**
	 * @return mixed
	 */
	public function getModelName()
	{
		return 'SproutEmail_CampaignType';
	}

	/**
	 * @return bool
	 * @throws Exception
	 * @throws \Exception
	 */
	public function save()
	{
		return sproutEmail()->campaignTypes->saveCampaignType($this->model);
	}

	public function getModelByHandle($handle = null)
	{
		$types = sproutEmail()->campaignTypes->getCampaignTypesByHandle($handle);

		return (!empty($types)) ? $types[0] : null;
	}

	public function deleteById($id)
	{
		return sproutEmail()->campaignTypes->deleteCampaignType($id);
	}

	public function setModel($model, $settings)
	{
		$model = parent::setModel($model, $settings);

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

			if ($model->fieldLayoutId != null)
			{
				// Remove previous field layout and update layout
				craft()->fields->deleteLayoutById($model->fieldLayoutId);
			}

			$fieldLayout = craft()->fields->assembleLayout($fieldLayouts);

			$fieldLayout->type = 'SproutEmail_CampaignEmail';

			$model->setFieldLayout($fieldLayout);
		}

		$this->model = $model;

		return $model;
	}
}