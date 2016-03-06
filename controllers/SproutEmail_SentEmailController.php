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
		$entry = sproutEmail()->sentEmails->getSentEmailById($entryId);

		$body = (!empty($entry->body)) ? $entry->body : null;
		$htmlBody = (!empty($entry->htmlBody)) ? $entry->htmlBody : null;

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
