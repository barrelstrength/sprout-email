<?php
namespace Craft;

class SproutEmail_SentEmailController extends BaseController
{
	public function actionGetResendModal()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$emailId   = craft()->request->getRequiredPost('emailId');
		$sentEmail = sproutEmail()->sentEmails->getSentEmailById($emailId);

		$content = craft()->templates->render('sproutemail/_modals/resendEmailPrepare', array(
			'sentEmail' => $sentEmail
		));

		$response          = new SproutEmail_ResponseModel();
		$response->content = $content;
		$response->success = true;

		$this->returnJson($response->getAttributes());
	}

	public function actionResendEmail()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$emailId   = craft()->request->getRequiredPost('emailId');
		$sentEmail = sproutEmail()->sentEmails->getSentEmailById($emailId);

		$recipients = array();

		if (craft()->request->getPost('recipients') !== null)
		{
			$recipients = craft()->request->getPost('recipients');

			$result = sproutEmail()->getValidAndInvalidRecipients($recipients);

			$invalidRecipients = $result['invalid'];
			$validRecipients   = $result['valid'];

			if (!empty($invalidRecipients))
			{
				$invalidEmails = implode(", ", $invalidRecipients);

				$message = Craft::t("Recipient email addresses do not validate: $invalidEmails");

				$response = SproutEmail_ResponseModel::createErrorModalResponse(
					'sproutemail/_modals/sendEmailConfirmation',
					array(
						'email'   => $sentEmail,
						'message' => Craft::t($message),
					)
				);

				$this->returnJson($response);
			}
		}
		else
		{
			$recipients[] = $sentEmail->toEmail;
		}

		$recipients = $validRecipients;

		try
		{
			$processedRecipients = array();
			$failedRecipients    = array();

			if (!empty($validRecipients))
			{
				foreach ($validRecipients as $validRecipient)
				{
					$recipientEmail = $validRecipient->email;

					$email            = new EmailModel();
					$email->toEmail   = $recipientEmail;
					$email->fromEmail = $sentEmail->fromEmail;
					$email->subject   = $sentEmail->title;
					$email->fromName  = $sentEmail->fromName;
					$email->body      = $sentEmail->body;
					$email->htmlBody  = $sentEmail->htmlBody;

					$infoTable = sproutEmail()->sentEmails->createInfoTableModel('sproutemail', array(
						'emailType'    => 'Resent Email',
						'deliveryType' => 'Live'
					));

					$variables = array(
						'email'               => $sentEmail,
						'renderedEmail'       => $email,
						'recipients'          => $recipients,
						'processedRecipients' => null,
						'info'                => $infoTable
					);

					if (sproutEmail()->sendEmail($email, $variables))
					{
						$processedRecipients[] = $email->toEmail;
					}
					else
					{
						$failedRecipients[] = $email->toEmail;
					}
				}

				if (!empty($failedRecipients))
				{
					$failedRecipientsText = implode(", ", $failedRecipients);

					$message = Craft::t("Failed to resend emails: $failedRecipientsText");

					throw new Exception($message);
				}

				if (!empty($processedRecipients))
				{
					$message = "Email sent successfully.";

					$response = SproutEmail_ResponseModel::createModalResponse(
						'sproutemail/_modals/sendEmailConfirmation',
						array(
							'email'   => $sentEmail,
							'message' => Craft::t($message)
						)
					);

					$this->returnJson($response);
				}
			}
		}
		catch (\Exception $e)
		{
			$response = SproutEmail_ResponseModel::createErrorModalResponse(
				'sproutemail/_modals/sendEmailConfirmation',
				array(
					'email'   => $sentEmail,
					'message' => Craft::t($e->getMessage()),
				)
			);

			$this->returnJson($response);
		}
	}

	/**
	 * Get HTML for Info Table HUD
	 *
	 * @throws HttpException
	 */
	public function actionGetInfoHtml()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$sentEmailId = craft()->request->getRequiredPost('sentEmailId');

		$sentEmail = sproutEmail()->sentEmails->getSentEmailById($sentEmailId);

		$html = craft()->templates->render('sproutemail/sentemails/_hud', array(
			'sentEmail' => $sentEmail
		));

		$response = array(
			'html' => $html
		);

		$this->returnJson(JsonHelper::encode($response));
	}
}
