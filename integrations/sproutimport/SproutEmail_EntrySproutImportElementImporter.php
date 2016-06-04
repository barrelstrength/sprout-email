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

	/**
	 * @return bool
	 * @throws Exception
	 * @throws \Exception
	 */
	public function save()
	{
		return sproutEmail()->entries->saveEntry($this->model);
	}
}