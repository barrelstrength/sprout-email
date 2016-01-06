<?php
namespace Craft;

class SproutEmail_SentEmailController extends BaseController
{
	public function actionShowSentEmailTemplate(array $variables = array())
	{
		$entryId = $variables['entryId'];

		$entry = SproutEmail_SentEmailRecord::model()->findById($entryId);
		$variables['entry']    = $entry;

		if(!empty($entry->htmlBody))
		{
			// Get only the body content
			preg_match("/<body[^>]*>(.*?)<\/body>/is", $entry->htmlBody, $matches);
			if(!empty($matches))
			{
				$htmlBody = $matches[1];
				$string = trim(preg_replace('/\s+/', ' ', $matches[1]));

				craft()->templates->includeJsResource('sproutemail/js/sentemail.js');
				craft()->templates->includeJs("
					 new Craft.SproutSentEmail('" . $string . "');
				");
			}
			else
			{
				$entry->body = $entry->htmlBody;
				$htmlBody = '';
			}
		}
		else
		{
			$htmlBody = '';
		}
		$body = (!empty($entry->body)) ? $entry->body : false;

		$variables['body'] 	   = $body;
		$variables['htmlBody'] = $htmlBody;

		$this->renderTemplate('sproutemail/sentemails/_view', $variables);
	}
}
