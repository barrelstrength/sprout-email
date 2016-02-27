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

		$entryId = craft()->request->getRequiredPost('entryId');
		$entry = sproutEmail()->sentemails->getSentEmailById($entryId);

		$htmlBody = '';
		$body = (!empty($entry->body)) ? $entry->body : null;

		if (!empty($entry->htmlBody))
		{
			// Get only the body content
			preg_match("/<body[^>]*>(.*?)<\/body>/is", $entry->htmlBody, $matches);

			if (!empty($matches))
			{
				$string = trim(preg_replace('/\s+/', ' ', $matches[1]));
				$htmlBody = $string;
			}
		}

		$content = craft()->templates->render('sproutemail/sentemails/_view', array(
			'entry'    => $entry,
			'body'     => $body,
			'htmlBody' => $htmlBody
		));

		$response = new SproutEmail_ResponseModel();
		$response->content = $content;
		$response->success = true;

		$this->returnJson($response->getAttributes());
	}
}
