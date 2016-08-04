<?php
namespace Craft;

class SproutEmail_NotificationEmailSproutImportElementImporter extends BaseSproutImportElementImporter
{
	/**
	 * @return mixed
	 */
	public function getModelName()
	{
		return 'SproutEmail_NotificationEmail';
	}

	/**
	 * @return bool
	 */
	public function hasSeedGenerator()
	{
		return false;
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

			$fieldLayout->type = 'SproutEmail_Notification';

			$model->setFieldLayout($fieldLayout);

			craft()->fields->saveLayout($fieldLayout);
		}

		if (isset($settings['attributes']['options']))
		{
			$_POST['rules'] = $settings['attributes']['options'];
		}

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
		return sproutEmail()->notificationEmails->saveNotification($this->model);
	}

	public function defineKeys()
	{
		// This will accept all attributes for notification model and fieldLayout to pass validation
		$notificationModel = new SproutEmail_NotificationEmailModel;

		$attributes = $notificationModel->defineAttributes();

		$attributeKeys = array_keys($attributes);

		return array_merge(array("fieldLayout"), $attributeKeys);
	}
}