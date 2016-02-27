<?php
namespace Craft;

class SproutEmail_SentEmailController extends BaseController
{
	/**
	 * Get the Sent Email View Content Modal
	 *
	 * @throws HttpException
	 */
	public function actionGetViewContentModal()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$variables = array();
		$entryId = craft()->request->getRequiredPost('entryId');

		$entry = sproutEmail()->sentemails->getSentEmailById($entryId);
		$variables['entry'] = $entry;

		if (!empty($entry->htmlBody))
		{
			// Get only the body content
			preg_match("/<body[^>]*>(.*?)<\/body>/is", $entry->htmlBody, $matches);
			if (!empty($matches))
			{
				$htmlBody = $matches[1];
				$string = trim(preg_replace('/\s+/', ' ', $matches[1]));
				$htmlBody = $string;
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

		$variables['body'] = $body;
		$variables['htmlBody'] = $htmlBody;

		$output = craft()->templates->render('sproutemail/sentemails/_view', $variables);

		$response = new SproutEmail_ResponseModel();
		$response->content = $output;
		$response->success = true;
		$this->returnJson($response->getAttributes());
	}
}
