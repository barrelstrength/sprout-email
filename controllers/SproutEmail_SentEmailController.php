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

	public function actionGetResendModal()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$entryId = craft()->request->getRequiredPost('entryId');
		$entry = sproutEmail()->sentEmails->getSentEmailById($entryId);

		$content = craft()->templates->render('sproutemail/_modals/resend', array(
			'entry'    => $entry
		));

		$response = new SproutEmail_ResponseModel();
		$response->content = $content;
		$response->success = true;

		$this->returnJson($response->getAttributes());
	}

	public function actionResendEmail()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();


		$entryId = craft()->request->getRequiredPost('entryId');
		$entry = sproutEmail()->sentEmails->getSentEmailById($entryId);

		$recipients = array();

		if(craft()->request->getPost('recipients') !== null)
		{
			$recipients = craft()->request->getPost('recipients');

			$result = sproutEmail()->getValidAndInvalidRecipients($recipients);

			$invalidRecipients = $result['invalid'];
			$validRecipients   = $result['valid'];

			if (!empty($invalidRecipients))
			{
				$invalidEmails = implode("<br />", $invalidRecipients);

				$message = Craft::t("Recipient email addresses do not validate: <br /> $invalidEmails");

				$response = SproutEmail_ResponseModel::createErrorModalResponse(
					'sproutemail/_modals/export',
					array(
						'entry'    => $entry,
						'message'  => Craft::t($message),
					)
				);

				$this->returnJson($response);
			}
		}
		else
		{
			$recipients[] = $entry->toEmail;
		}

		$recipients = $validRecipients;



		try
		{
			$processedRecipients = array();
			$failedRecipients    = array();

			if (!empty($validRecipients))
			{
				$pluginVersion = craft()->plugins->getPlugin('sproutemail')->getVersion();

				foreach ($validRecipients as $validRecipient)
				{
					$recipientEmail = $validRecipient->email;

					$email = new EmailModel();
					$email->toEmail   = $recipientEmail;
					$email->fromEmail = $entry->fromEmail;
					$email->subject   = $entry->title;
					$email->fromName  = $entry->fromName;
					$email->body      = $entry->body;
					$email->htmlBody  = $entry->htmlBody;

					$variables = array(
						'email'               => $entry,
						'renderedEmail'       => $email,
						'recipients'          => $recipients,
						'processedRecipients' => null,
						'sproutemail'         => array(
							'info' => array(
								'source'        => 'Sprout Email',
								'sourceVersion' => 'Sprout Email ' . $pluginVersion,
								'emailType'     => 'Resend Failed Email',
								'testEmail'     => false
							)
						)

					);

					if (sproutEmail()->sendEmail($email, $variables))
					{
						$processedRecipients[] = $email->toEmail;
					}
					else
					{
						$failedRecipients[] = $email;
					}
				}

				if (!empty($failedRecipients))
				{
					$failedRecipientsText = implode("<br />", $failedRecipients);

					$message = Craft::t("Failed to resend emails: <br /> $failedRecipientsText");

					throw new Exception($message);
				}

				if (!empty($processedRecipients))
				{
					$processedRecipients = implode("<br />", $processedRecipients);

					$message = Craft::t("Emails resent: \n $processedRecipients");

					$response = SproutEmail_ResponseModel::createModalResponse(
						'sproutemail/_modals/export',
						array(
							'entry'         => $entry,
							'message'       => Craft::t($message)
						)
					);

					$this->returnJson($response);
				}
			}
		}
		catch (\Exception $e)
		{
			$response = SproutEmail_ResponseModel::createErrorModalResponse(
				'sproutemail/_modals/export',
				array(
					'entry'    => $entry,
					'message'  => Craft::t($e->getMessage()),
				)
			);

			$this->returnJson($response);
		}

	}
}
