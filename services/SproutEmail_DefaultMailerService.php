<?php
namespace Craft;

/**
 * Class SproutEmail_DefaultMailerService
 *
 * @package Craft
 */
class SproutEmail_DefaultMailerService extends BaseApplicationComponent
{
	/**
	 * @var Model
	 */
	protected $settings;

	/**
	 * Whether lists and recipients should be created dynamically based on Sprout Commerce events
	 *
	 * @return mixed
	 */
	public function enableDynamicLists()
	{
		if (is_null($this->settings))
		{
			$mailer = sproutEmail()->mailers->getMailerByName('defaultmailer');

			if ($mailer)
			{
				$this->init();

				$this->settings = $mailer->getSettings();
			}
		}

		return $this->settings->enableDynamicLists;
	}

	/**
	 * @param int $id
	 *
	 * @return SproutEmail_DefaultMailerRecipientModel|null
	 */
	public function getRecipientById($id)
	{
		$record = SproutEmail_DefaultMailerRecipientRecord::model()->with('recipientLists')->findById((int) $id);

		if ($record)
		{
			return SproutEmail_DefaultMailerRecipientModel::populateModel($record);
		}
	}

	/**
	 * @param string $email
	 *
	 * @return SproutEmail_DefaultMailerRecipientModel|null
	 */
	public function getRecipientByEmail($email)
	{
		$record = SproutEmail_DefaultMailerRecipientRecord::model()->with('recipientLists')->findByAttributes(array('email' => $email));

		if ($record)
		{
			return SproutEmail_DefaultMailerRecipientModel::populateModel($record);
		}
	}

	/**
	 * @param int $id
	 *
	 * @return SproutEmail_DefaultMailerRecipientListModel|null
	 */
	public function getRecipientListById($id)
	{
		if (($record = SproutEmail_DefaultMailerRecipientListRecord::model()->with('recipients')->findById((int) $id)))
		{
			$record->recipients = SproutEmail_DefaultMailerRecipientModel::populateModels($record->recipients);

			return SproutEmail_DefaultMailerRecipientListModel::populateModel($record);
		}
	}

	/**
	 * @param int $emailId
	 *
	 * @return SproutEmail_DefaultMailerRecipientListModel[]|null
	 */
	public function getRecipientListsByEmailId($emailId)
	{
		if (($record = SproutEmail_EntryRecipientListRecord::model()->with('entry')->findAll('emailId=:emailId', array(':emailId' => $emailId))))
		{
			$recipientLists = array();

			foreach ($record as $entryRecipientList)
			{
				$recipientLists[] = $this->getRecipientListById($entryRecipientList->list);
			}

			return $recipientLists;
		}
	}

	/**
	 * @param SproutEmail_CampaignEmailModel $campaignEmail
	 * @param mixed                          $element
	 *
	 * @return array
	 */
	public function getRecipientsFromCampaignEmailModel($campaignEmail, $element)
	{
		$recipients         = array();
		$onTheFlyRecipients = $campaignEmail->getRecipients($element);

		if (is_string($onTheFlyRecipients))
		{
			$onTheFlyRecipients = explode(",", $onTheFlyRecipients);
		}

		if (count($onTheFlyRecipients))
		{
			foreach ($onTheFlyRecipients as $index => $recipient)
			{
				$recipients[$index] = SproutEmail_SimpleRecipientModel::create(
					array(
						'firstName' => '',
						'lastName'  => '',
						'email'     => $recipient
					)
				);
			}
		}

		return $recipients;
	}

	/**
	 * @param int $handle
	 *
	 * @return SproutEmail_DefaultMailerRecipientListModel|null
	 */
	public function getRecipientListByHandle($handle)
	{
		if (($record = SproutEmail_DefaultMailerRecipientListRecord::model()->with('recipients')->findByAttributes(array('handle' => $handle))))
		{
			return SproutEmail_DefaultMailerRecipientListModel::populateModel($record);
		}
	}

	/**
	 * @return SproutEmail_DefaultMailerRecipientModel[]|null
	 */
	public function getRecipients()
	{
		$records = SproutEmail_DefaultMailerRecipientRecord::model()->with('recipientLists')->findAll();

		if ($records)
		{
			return SproutEmail_DefaultMailerRecipientModel::populateModels($records);
		}
	}

	/**
	 * @param array $listIds
	 *
	 * @return SproutEmail_DefaultMailerRecipientModel[]|array
	 */
	public function getRecipientsByRecipientListIds(array $listIds = null)
	{
		$recipients = array();

		if ($listIds && count($listIds))
		{
			foreach ($listIds as $listId)
			{
				$list = $this->getRecipientListById($listId);

				if ($list && isset($list->recipients) && count($list->recipients))
				{
					foreach ($list->recipients as $recipient)
					{
						$recipients[$recipient->id] = SproutEmail_DefaultMailerRecipientModel::populateModel($recipient);
					}
				}
			}
		}

		return $recipients;
	}

	/**
	 * @return SproutEmail_DefaultMailerRecipientListModel[]|null
	 */
	public function getRecipientLists()
	{
		$records = SproutEmail_DefaultMailerRecipientListRecord::model()->with('recipients')->findAll();

		if ($records)
		{
			return SproutEmail_DefaultMailerRecipientListModel::populateModels($records);
		}
	}

	/**
	 * @param int $id
	 *
	 * @return SproutEmail_DefaultMailerRecipientListModel[]|null
	 */
	public function getRecipientListsByRecipientId($id)
	{
		$lists         = array();
		$record        = SproutEmail_DefaultMailerRecipientListRecipientRecord::model();
		$relationships = $record->findAllByAttributes(array('recipientId' => $id));

		if (count($relationships))
		{
			foreach ($relationships as $relationship)
			{
				$lists[] = sproutEmailDefaultMailer()->getRecipientListById($relationship->recipientListId);
			}
		}

		return $lists;
	}

	/**
	 * @param null $element
	 *
	 * @return \Twig_Markup
	 */
	public function getRecipientListsHtml($element = null, $default = array())
	{
		$lists   = $this->getRecipientLists();
		$options = array();

		if (count($lists))
		{
			foreach ($lists as $list)
			{
				$options[] = array(
					'label' => sprintf('%s (%d)', $list->name, count($list->recipients)),
					'value' => $list->id
				);
			}
		}

		$values = array();

		if (count($element->getRecipientListIds()))
		{
			$values = $element->getRecipientListIds();
		}

		if (!empty($default))
		{
			$values = $default;
		}

		// @todo - Move template code to the template
		$checkboxGroup = craft()->templates->renderMacro(
			'_includes/forms', 'checkboxGroup', array(
				array(
					'name'    => 'recipient[recipientLists]',
					'options' => $options,
					'values'  => $values
				)
			)
		);

		$html = craft()->templates->renderMacro(
			'_includes/forms', 'field', array(
				array(
					'id'     => 'recipientLists',
					'errors' => $element->getErrors('recipientLists')
				),
				$checkboxGroup
			)
		);

		return TemplateHelper::getRaw($html);
	}

	public function saveRecipientList(SproutEmail_DefaultMailerRecipientListModel &$model)
	{
		if (isset($model->id) && is_numeric($model->id))
		{
			$record = SproutEmail_DefaultMailerRecipientListRecord::model()->findById($model->id);

			if ($record)
			{
				$record->setAttributes($model->getAttributes(), false);
			}
		}
		else
		{
			$record = new SproutEmail_DefaultMailerRecipientListRecord();

			$record->name    = $model->name;
			$record->handle  = $model->handle;
			$record->dynamic = (int) $model->dynamic;
		}

		if ($record->validate())
		{
			try
			{
				$record->save(false);

				$model->id = $record->id;

				return true;
			}
			catch (\Exception $e)
			{
				$model->addError('save', $e->getMessage());
			}
		}
		else
		{
			$model->addErrors($record->getErrors());
		}

		return false;
	}

	public function saveRecipient(SproutEmail_DefaultMailerRecipientModel &$model)
	{
		$isNewElement = !$model->id;

		if (!$isNewElement)
		{
			$record = SproutEmail_DefaultMailerRecipientRecord::model()->findById($model->id);

			if (!$record)
			{
				throw new Exception(Craft::t('No recipient exists with the id “{id}”', array('id' => $model->id)));
			}
		}
		else
		{
			$record = new SproutEmail_DefaultMailerRecipientRecord();
		}

		$record->setAttributes($model->getAttributes(), false);

		$record->validate();

		$model->addErrors($record->getErrors());

		if (!$model->hasErrors())
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

			try
			{
				if (craft()->elements->saveElement($model, true))
				{
					$saved = false;

					if ($isNewElement)
					{
						$record->id = $model->id;
					}

					if ($record->save(false))
					{
						$model->id = $record->id;

						$saved = $this->saveRecipientListRecipientRelations($record->id, $model->recipientLists);
					}

					if ($transaction !== null && $saved)
					{
						$transaction->commit();
					}

					return $saved;
				}

				$model->addErrors($record->getErrors());
			}
			catch (\Exception $e)
			{
				if ($transaction !== null)
				{
					$transaction->rollback();
				}

				throw $e;
			}
		}

		return false;
	}

	/**
	 * @param int   $recipientId
	 * @param array $recipientListIds
	 *
	 * @throws Exception
	 *
	 * @return bool
	 */
	public function saveRecipientListRecipientRelations($recipientId, array $recipientListIds = array())
	{
		try
		{
			SproutEmail_DefaultMailerRecipientListRecipientRecord::model()->deleteAll('recipientId = :recipientId', array(':recipientId' => $recipientId));
		}
		catch (Exception $e)
		{
			sproutEmail()->error($e->getMessage());
		}

		if (count($recipientListIds))
		{
			foreach ($recipientListIds as $listId)
			{
				$list = $this->getRecipientListById($listId);

				if ($list)
				{
					$relation = new SproutEmail_DefaultMailerRecipientListRecipientRecord();

					$relation->recipientId     = $recipientId;
					$relation->recipientListId = $list->id;

					if (!$relation->save(false))
					{
						throw new Exception(print_r($relation->getErrors(), true));
					}
				}
				else
				{
					throw new Exception(
						Craft::t(
							'The recipient list with id {listId} does not exists.',
							array('listId' => $listId)
						)
					);
				}
			}
		}

		return true;
	}

	/**
	 * @param SproutEmail_CampaignTypeModel $campaign
	 * @param mixed|null                    $object
	 * @param bool                          $useMockData
	 *
	 * @return bool
	 */
	public function sendNotification($campaign, $object = null, $useMockData = false)
	{
		$email             = new EmailModel();
		$notificationEmail = $campaign->getNotificationEmail();

		// Allow disabled emails to be tested
		if (!$notificationEmail->isReady() AND !$useMockData)
		{
			return false;
		}

		$recipients = $this->prepareRecipients($notificationEmail, $object, $useMockData);

		if (empty($recipients))
		{
			sproutEmail()->error(Craft::t('No recipients found.'));

			return false;
		}

		// Pass this variable for logging sent error
		$emailModel = $email;

		$email = $this->renderEmailTemplates($email, $campaign, $notificationEmail, $object);

		if (!$email)
		{
			// Template error
			$message = sproutEmail()->getError('template');

			// Double check this may be a bug
			//	sproutEmail()->handleOnSendEmailErrorEvent($message, $emailModel);
		}

		if (empty($email->body) OR empty($email->htmlBody))
		{
			return false;
		}

		$processedRecipients = array();

		foreach ($recipients as $recipient)
		{
			$email->toEmail     = sproutEmail()->renderObjectTemplateSafely($recipient->email, $object);
			$email->toFirstName = $recipient->firstName;
			$email->toLastName  = $recipient->lastName;

			if (array_key_exists($email->toEmail, $processedRecipients))
			{
				continue;
			}

			try
			{
				$infoTable = sproutEmail()->sentEmails->createInfoTableModel('sproutemail', array(
					'emailType'    => 'Notification',
					'deliveryType' => ($useMockData ? 'Test' : 'Live')
				));

				$variables = array(
					'email'               => $notificationEmail,
					'renderedEmail'       => $email,
					'campaign'            => $campaign,
					'object'              => $object,
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
					return false;
				}
			}
			catch (\Exception $e)
			{
				sproutEmail()->error($e->getMessage());
			}
		}

		// Trigger on send notification event
		if (!empty($processedRecipients))
		{
			$variables['processedRecipients'] = $processedRecipients;
		}

		return true;
	}

	/**
	 * @param SproutEmail_CampaignEmailModel $campaignEmail
	 * @param SproutEmail_CampaignTypeModel  $campaign
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function exportEmail(SproutEmail_CampaignEmailModel $campaignEmail, SproutEmail_CampaignTypeModel $campaign)
	{
		$response = array();

		$params = array(
			'email'    => $campaignEmail,
			'campaign' => $campaign,

			// @deprecate - in favor of `email` in v3
			'entry'    => $campaignEmail
		);

		$email = array(
			'fromEmail' => $campaignEmail->fromEmail,
			'fromName'  => $campaignEmail->fromName,
			'subject'   => $campaignEmail->subjectLine,
		);

		if ($campaignEmail->replyToEmail && filter_var($campaignEmail->replyToEmail, FILTER_VALIDATE_EMAIL))
		{
			$email['replyToEmail'] = $campaignEmail->replyToEmail;
		}

		$recipients = array();

		if (($entryRecipientLists = SproutEmail_EntryRecipientListRecord::model()->findAllByAttributes(array('emailId' => $campaignEmail->id))))
		{
			foreach ($entryRecipientLists as $entryRecipientList)
			{
				if (($recipientList = $this->getRecipientListById($entryRecipientList->list)))
				{
					$recipients = array_merge($recipients, $recipientList->recipients);
				}
			}
		}

		$email = EmailModel::populateModel($email);

		foreach ($recipients as $recipient)
		{
			try
			{
				$params['recipient'] = $recipient;
				$email->body         = sproutEmail()->renderSiteTemplateIfExists($campaign->template . '.txt', $params);
				$email->htmlBody     = sproutEmail()->renderSiteTemplateIfExists($campaign->template, $params);

				$email->setAttribute('toEmail', $recipient->email);
				$email->setAttribute('toFirstName', $recipient->firstName);
				$email->setAttribute('toLastName', $recipient->lastName);

				sproutEmail()->sendEmail($email);
			}
			catch (\Exception $e)
			{
				throw $e;
			}
		}

		$response['emailModel'] = $email;

		return $response;
	}

	/**
	 * Handles sproutCommerce.saveProduct events to create dynamic recipient lists
	 *
	 * @param Event $event
	 */
	public function handleSaveProduct(Event $event)
	{
		// New entries create a new dynamic recipient list
		if (isset($event->params['isNewProduct']) && $event->params['isNewProduct'])
		{
			$list = new SproutEmail_DefaultMailerRecipientListModel();

			$list->name    = $event->params['product']->title;
			$list->handle  = sproutEmail()->createHandle($event->params['product']->title);
			$list->dynamic = 1;

			try
			{
				sproutEmailDefaultMailer()->saveRecipientList($list);
			}
			catch (\Exception $e)
			{
				sproutEmail()->error($e->getMessage());
			}
		}
	}

	/**
	 * Handles sproutCommerce.checkoutEnd events to add dynamic recipients to a lists
	 *
	 * @param Event $event
	 */
	public function handleCheckoutEnd(Event $event)
	{
		if (isset($event->params['checkout']->success) && $event->params['checkout']->success)
		{
			$order = $event->params['checkout']->order;

			if (count($order->products) && count($order->payments))
			{
				foreach ($order->products as $purchasedProduct)
				{
					$list = sproutEmailDefaultMailer()->getRecipientListByHandle($purchasedProduct->product->slug);

					if ($list)
					{
						foreach ($order->payments as $payment)
						{
							$recipient                 = new SproutEmail_DefaultMailerRecipientModel();
							$recipient->email          = $payment->email;
							$recipient->firstName      = $payment->firstName;
							$recipient->lastName       = $payment->lastName;
							$recipient->recipientLists = array($list->id);

							try
							{
								if (!sproutEmailDefaultMailer()->saveRecipient($recipient))
								{
									sproutEmail()->error($recipient->getErrors());
								}
							}
							catch (\Exception $e)
							{
								sproutEmail()->error($e->getMessage());
							}
						}
					}
				}
			}
		}
	}

	/**
	 * @param $email
	 * @param $object
	 * @param $useMockData
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function prepareRecipients($email, $object, $useMockData)
	{
		// Get recipients for test notifications
		if ($useMockData)
		{
			$recipients = craft()->request->getPost('recipients');

			if (empty($recipients))
			{
				return array();
			}

			$recipients = craft()->request->getPost('recipients');

			$result = sproutEmail()->getValidAndInvalidRecipients($recipients);

			$invalidRecipients = $result['invalid'];
			$validRecipients   = $result['valid'];

			if (!empty($invalidRecipients))
			{
				$invalidEmails = implode("<br />", $invalidRecipients);

				throw new Exception(Craft::t("Recipient email addresses do not validate: <br /> {invalidEmails}", array(
					'invalidEmails' => $invalidEmails
				)));
			}

			return $validRecipients;
		}

		// Get recipients for live emails
		$entryRecipients   = $this->getRecipientsFromCampaignEmailModel($email, $object);
		$dynamicRecipients = sproutEmail()->notificationEmails->getDynamicRecipientsFromElement($object);
		$listRecipients    = $this->getListRecipients($email);

		$recipients = array_merge(
			$listRecipients,
			$entryRecipients,
			$dynamicRecipients
		);

		return $recipients;
	}

	/**
	 * @param $email
	 *
	 * @return array|SproutEmail_DefaultMailerRecipientModel[]
	 */
	protected function getListRecipients($email)
	{
		$listIds        = array();
		$recipientLists = $this->getRecipientListsByEmailId($email->id);

		if ($recipientLists && count($recipientLists))
		{
			foreach ($recipientLists as $list)
			{
				$listIds[] = $list->id;
			}
		}

		$listRecipients = $this->getRecipientsByRecipientListIds($listIds);

		return $listRecipients;
	}

	/**
	 * @param EmailModel $emailModel
	 * @param            $campaign
	 * @param            $email
	 * @param            $object
	 *
	 * @return bool|EmailModel
	 */
	public function renderEmailTemplates(EmailModel $emailModel, $template = '', $email, $object)
	{
		// Render Email Entry fields that have dynamic values
		$emailModel->subject   = sproutEmail()->renderObjectTemplateSafely($email->subjectLine, $object);
		$emailModel->fromName  = sproutEmail()->renderObjectTemplateSafely($email->fromName, $object);
		$emailModel->fromEmail = sproutEmail()->renderObjectTemplateSafely($email->fromEmail, $object);
		$emailModel->replyTo   = sproutEmail()->renderObjectTemplateSafely($email->replyToEmail, $object);

		// Render the email templates
		$emailModel->body     = sproutEmail()->renderSiteTemplateIfExists($template . '.txt', array(
			'email'        => $email,
			'object'       => $object,

			// @deprecate in v3 in favor of the `email` variable
			'entry'        => $email,
			'notification' => $email
		));
		$emailModel->htmlBody = sproutEmail()->renderSiteTemplateIfExists($template, array(
			'email'        => $email,
			'object'       => $object,

			// @deprecate in v3 in favor of the `email` variable
			'entry'        => $email,
			'notification' => $email
		));

		$styleTags = array();

		$htmlBody = $this->addPlaceholderStyleTags($emailModel->htmlBody, $styleTags);

		// Some Twig code in our email fields may need us to decode
		// entities so our email doesn't throw errors when we try to
		// render the field objects. Example: {variable|date("Y/m/d")}
		$emailModel->body = HtmlHelper::decode($emailModel->body);
		$htmlBody         = HtmlHelper::decode($htmlBody);

		// Process the results of the template s once more, to render any dynamic objects used in fields
		$emailModel->body     = sproutEmail()->renderObjectTemplateSafely($emailModel->body, $object);
		$emailModel->htmlBody = sproutEmail()->renderObjectTemplateSafely($htmlBody, $object);

		$emailModel->htmlBody = $this->removePlaceholderStyleTags($emailModel->htmlBody, $styleTags);

		$templateError = sproutEmail()->getError('template');

		if (!empty($templateError))
		{
			$emailModel->htmlBody = $templateError;
		}

		return $emailModel;
	}

	public function addPlaceholderStyleTags($htmlBody, &$styleTags)
	{
		// Get the style tag
		preg_match_all("/<style\\b[^>]*>(.*?)<\\/style>/s", $htmlBody, $matches);

		$results = array();

		if (!empty($matches))
		{
			$tags = $matches[0];

			// Temporarily replace with style tags with a random string
			if (!empty($tags))
			{
				$i = 0;
				foreach ($tags as $tag)
				{
					$key = "<!-- %style$i% -->";

					$styleTags[$key] = $tag;

					$htmlBody = str_replace($tag, $key, $htmlBody);

					$i++;
				}
			}
		}

		return $htmlBody;
	}

	// Put back the style tag after object is rendered if style tag is found
	public function removePlaceholderStyleTags($htmlBody, $styleTags)
	{
		if (!empty($styleTags))
		{
			foreach ($styleTags as $key => $tag)
			{
				$htmlBody = str_replace($key, $tag, $htmlBody);
			}
		}

		return $htmlBody;
	}
}
