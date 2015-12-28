<?php
namespace Craft;

class SproutEmail_SentEmailController extends BaseController
{
	public function actionShowSentEmailTemplate(array $variables = array())
	{
		$entryId = $variables['entryId'];

		$entry = SproutEmail_SentEmailRecord::model()->find($entryId);
		$variables['entry'] = $entry;
		$this->renderTemplate('sproutemail/sentemails/_view', $variables);
	}
}
