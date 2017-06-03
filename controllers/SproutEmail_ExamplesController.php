<?php
namespace Craft;

class SproutEmail_ExamplesController extends BaseController
{
	/**
	 * Install examples
	 *
	 * @return void
	 */
	public function actionInstall()
	{
		$this->installExampleTemplates();
		$this->installExampleData();
		$this->installExampleNotificationData();

		craft()->userSession->setNotice(
			Craft::t('Examples successfully installed.')
		);

		$this->redirect(UrlHelper::getCpUrl() . '/sproutemail');
	}

	/**
	 * Install templates
	 *
	 * @return void
	 */
	private function installExampleTemplates()
	{
		try
		{
			$fileHelper = new \CFileHelper();
			$path       = craft()->path->getSiteTemplatesPath();

			$dir = @mkdir($path . 'sproutemail');

			$fileHelper->copyDirectory(craft()->path->getPluginsPath() . 'sproutemail/templates/_special/examples/emails',
				craft()->path->getSiteTemplatesPath() . 'sproutemail');

			$message = Craft::t("Example templates and entries has been created.");

			craft()->userSession->setNotice($message);
		}
		catch (\Exception $e)
		{
			$this->handleError($e);
		}
	}

	/**
	 * Install example data
	 *
	 * @return void
	 */
	private function installExampleData()
	{
		try
		{
			// Create Example Emails
			// ------------------------------------------------------------

			$emailSettings = array(
				array(
					'name'              => 'Monthly Newsletter',
					'handle'            => 'monthlyNewsletter',
					'type'              => 'email',
					'mailer'            => 'copypaste',
					'hasUrls'           => true,
					'hasAdvancedTitles' => false,
					'urlFormat'         => 'sproutemail/{slug}',
					'template'          => 'sproutemail/newsletter',
					'templateCopyPaste' => 'sproutemail/newsletter'
				),
			);

			$fieldSettings = array(
				'monthlyNewsletter' => array(
					'Content' => array(
						array(
							'name'         => 'HTML Email Body',
							'handle'       => 'exampleHtmlEmailBody',
							'instructions' => '',
							'type'         => 'RichText',
							'required'     => 1,
							'settings'     => array(
								'configFile'  => '',
								'cleanupHtml' => '1',
								'purifyHtml'  => '',
								'columnType'  => 'mediumtext'
							)
						),
						array(
							'name'     => 'Text Email Body',
							'handle'   => 'exampleTextEmailBody',
							'type'     => 'PlainText',
							'required' => 1,
							'settings' => array(
								'placeholder' => '',
								'maxLength'   => '',
								'multiline'   => 1,
								'initialRows' => 4,
							)
						)
					)
				),
			);

			$currentUser = craft()->userSession->getUser();

			$emailExamples = array(
				'welcomeEmail'      => array(
					'title'          => 'Welcome!',
					'subjectLine'    => 'Welcome!',
					'slug'           => 'welcome',
					'uri'            => null,
					'campaignTypeId' => null,
					'sproutEmail'    => array(
						'fromName'     => craft()->getSiteName(),
						'fromEmail'    => $currentUser->email,
						'replyToEmail' => $currentUser->email,
					),
					'recipient'      => array(
						'onTheFlyRecipients' => '{email}',
					),
					'rules'          => array(
						'craft' => array(
							'saveUser' => array(
								'whenNew'      => '1',
								'whenUpdated'  => '',
								'userGroupIds' => ''
							)
						),
					),
					'enabled'        => true,
					'archived'       => '0',
					'locale'         => $currentUser->locale,
					'localeEnabled'  => '1',
					'htmlBody'       => '<p>Thanks for becoming a member.</p>
<ul>
	<li>Username: <strong>{username}</strong></li>
	<li>Email: <strong>{email}</strong></li>
</ul>',
					'textBody'       => 'Thanks for becoming a member.

Username: {username}
Email: {email}',
				),
				'newUserEmail'      => array(
					'title'          => 'A new user has created an account',
					'subjectLine'    => 'A new user has created an account',
					'slug'           => 'a-new-user-has-created-an-account',
					'uri'            => null,
					'campaignTypeId' => null,
					'sproutEmail'    => array(
						'fromName'     => craft()->getSiteName(),
						'fromEmail'    => $currentUser->email,
						'replyToEmail' => $currentUser->email,
					),
					'recipient'      => array(
						'onTheFlyRecipients' => $currentUser->email,
					),
					'rules'          => array(
						'craft' => array(
							'saveUser' => array(
								'whenNew'      => '1',
								'whenUpdated'  => '',
								'userGroupIds' => ''
							)
						)
					),
					'enabled'        => true,
					'archived'       => '0',
					'locale'         => $currentUser->locale,
					'localeEnabled'  => '1',
					'htmlBody'       => '<p>A new user has been created:</p>
<ul>
	<li>Username: <strong>{username}</strong></li>
	<li>Email: <strong>{email}</strong></li>
</ul>',
					'textBody'       => 'A new user has been created:

Username: {username}
Email: {email}',
				),
				'monthlyNewsletter' => array(
					'title'          => 'Best Practices for your Email Subject Line',
					'subjectLine'    => 'Best Practices for your Email Subject Line',
					'slug'           => 'best-practices-for-your-email-subject-line',
					'uri'            => 'sproutemail/best-practices-for-your-email-subject-line',
					'campaignTypeId' => null,
					'sproutEmail'    => array(
						'fromName'     => craft()->getSiteName(),
						'fromEmail'    => $currentUser->email,
						'replyToEmail' => $currentUser->email,
					),
					'recipient'      => array(),
					'rules'          => array(),
					'enabled'        => true,
					'archived'       => '0',
					'locale'         => $currentUser->locale,
					'localeEnabled'  => '1',
					'htmlBody'       => '<p>Say something interesting!</p>',
					'textBody'       => 'Say something interesting!',
				)
			);

			// Create Emails and their Content
			foreach ($emailSettings as $settings)
			{
				$campaignType = new SproutEmail_CampaignTypeModel();

				// Assign our email settings
				$campaignType->name              = $settings['name'];
				$campaignType->handle            = $settings['handle'];
				$campaignType->mailer            = $settings['mailer'];
				$campaignType->hasUrls           = $settings['hasUrls'];
				$campaignType->urlFormat         = $settings['urlFormat'];
				$campaignType->hasAdvancedTitles = $settings['hasAdvancedTitles'];
				$campaignType->template          = $settings['template'];
				$campaignType->templateCopyPaste = $settings['templateCopyPaste'];

				// Create the Email
				if (!$campaignType = sproutEmail()->campaignTypes->saveCampaignType($campaignType))
				{
					SproutEmailPlugin::log('Campaign NOT CREATED', LogLevel::Error);

					return false;
				}

				//------------------------------------------------------------

				// Do we have a new field that doesn't exist yet?
				// If so, save it and grab the id.

				$fieldLayout    = array();
				$requiredFields = array();

				$tabs = $fieldSettings[$campaignType->handle];

				// Ensure we have a Field Group to save our Fields
				if (!$sproutEmailFieldGroup = $this->createFieldGroup())
				{
					SproutEmailPlugin::log('Could not save the Sprout Email Examples field group.', LogLevel::Warning);

					craft()->userSession->setError(Craft::t('Unable to create examples. Field group not saved.'));

					return false;
				}

				foreach ($tabs as $tabName => $newFields)
				{
					foreach ($newFields as $newField)
					{
						if (!$field = craft()->fields->getFieldByHandle($newField['handle']))
						{
							$field           = new FieldModel();
							$field->groupId  = $sproutEmailFieldGroup->id;
							$field->name     = $newField['name'];
							$field->handle   = $newField['handle'];
							$field->type     = $newField['type'];
							$field->required = $newField['required'];
							$field->settings = $newField['settings'];

							// Save our field
							craft()->fields->saveField($field);
						}

						$fieldLayout[$tabName][] = $field->id;

						if ($field->required)
						{
							$requiredFields[] = $field->id;
						}
					}
				}

				// Set the field layout
				$fieldLayout = craft()->fields->assembleLayout($fieldLayout, $requiredFields);

				$fieldLayout->type = 'SproutEmail_CampaignEmail';
				$campaignType->setFieldLayout($fieldLayout);

				// Save our email again with a layout
				sproutEmail()->campaignTypes->saveCampaignType($campaignType);

				$campaignEmailRecord = SproutEmail_CampaignEmailRecord::model()->findByAttributes(array(
					'campaignTypeId' => $campaignType->id
				));

				if (!$campaignEmailRecord)
				{
					$campaignEmail = new SproutEmail_CampaignEmailModel();
				}
				else
				{
					$campaignEmail = SproutEmail_CampaignEmailModel::populateModel($campaignEmailRecord->getAttributes());
				}

				$emailData = $emailExamples[$campaignType->handle];

				$_POST['sproutEmail'] = $emailData['sproutEmail'];
				$_POST['recipient']   = $emailData['recipient'];
				$_POST['rules']       = $emailData['rules'];

				unset($emailData['recipient']);
				unset($emailData['rules']);

				$campaignEmail->setAttributes($emailData);
				$campaignEmail->campaignTypeId = $campaignType->id;
				$campaignEmail->fromName       = craft()->request->getPost('sproutEmail.fromName');
				$campaignEmail->fromEmail      = craft()->request->getPost('sproutEmail.fromEmail');
				$campaignEmail->replyToEmail   = craft()->request->getPost('sproutEmail.replyToEmail');

				$campaignEmail->getContent()->title                = $emailData['title'];
				$campaignEmail->getContent()->exampleHtmlEmailBody = $emailData['htmlBody'];
				$campaignEmail->getContent()->exampleTextEmailBody = $emailData['textBody'];

				sproutEmail()->campaignEmails->saveCampaignEmail($campaignEmail, $campaignType);
			}
		}
		catch (\Exception $e)
		{
			$this->handleError($e);
		}
	}

	private function installExampleNotificationData()
	{
		$currentUser = craft()->userSession->getUser();

		try
		{
			$notificationEmails = array(
				'welcomeEmail' => array(
					'name'              => 'Welcome Email - User Notification',
					'handle'            => 'welcomeEmail',
					'type'              => 'notification',
					'hasUrls'           => false,
					'urlFormat'         => null,
					'hasAdvancedTitles' => false,
					'template'          => 'sproutemail/notification',
					'templateCopyPaste' => null,
					'eventId'           => 'SproutEmail-users-saveUser',
					'options'           => array(
						'craft' => array(
							'saveUser'          => array(
								'whenNew'      => 1,
								'whenUpdated'  => '',
								'userGroupIds' => '*'
							),
							'saveCampaignEmail' => array(
								'whenNew'     => '',
								'whenUpdated' => '',
								'sectionIds'  => '*'
							)
						)
					),
					'title'             => 'Welcome!',
					'subjectLine'       => 'Welcome!',
					'recipients'        => $currentUser->email,
					'fromName'          => craft()->getSiteName(),
					'fromEmail'         => $currentUser->email,
					'replyToEmail'      => $currentUser->email,
					'locale'            => $currentUser->locale,
					'htmlBody'          => '<p>Thanks for becoming a member.</p>
															<ul>
																<li>Username: <strong>{username}</strong></li>
																<li>Email: <strong>{email}</strong></li>
															</ul>',
					'textBody'          => 'Thanks for becoming a member.
Username: {username}
Email: {email}'
				),
				'newUserEmail' => array(
					'name'              => 'New User - Admin Notification',
					'handle'            => 'welcomeEmail',
					'type'              => 'notification',
					'hasUrls'           => false,
					'urlFormat'         => null,
					'hasAdvancedTitles' => false,
					'template'          => 'sproutemail/notification',
					'templateCopyPaste' => null,
					'eventId'           => 'SproutEmail-users-saveUser',
					'options'           => array(
						'craft' => array(
							'saveUser'  => array(
								'whenNew'      => 1,
								'whenUpdated'  => '',
								'userGroupIds' => '*'
							),
							'saveEntry' => array(
								'whenNew'     => '',
								'whenUpdated' => '',
								'sectionIds'  => '*'
							)
						)
					),
					'title'             => 'New User - Admin Notification',
					'subjectLine'       => 'New User - Admin Notification',
					'recipients'        => $currentUser->email,
					'fromName'          => craft()->getSiteName(),
					'fromEmail'         => $currentUser->email,
					'replyToEmail'      => $currentUser->email,
					'locale'            => $currentUser->locale,
					'htmlBody'          => '<p>A new user has been created:</p>
<ul>
	<li>Username: <strong>{username}</strong></li>
	<li>Email: <strong>{email}</strong></li>
</ul>',
					'textBody'          => 'A new user has been created:

Username: {username}
Email: {email}',
				),
			);

			$fieldSettings = array(
				'welcomeEmail' => array(
					'Content' => array(
						array(
							'name'         => 'HTML Email Body',
							'handle'       => 'exampleHtmlEmailBody',
							'instructions' => '',
							'type'         => 'RichText',
							'required'     => 1,
							'settings'     => array(
								'configFile'  => '',
								'cleanupHtml' => '1',
								'purifyHtml'  => '',
								'columnType'  => 'mediumtext'
							)
						),
						array(
							'name'     => 'Text Email Body',
							'handle'   => 'exampleTextEmailBody',
							'type'     => 'PlainText',
							'required' => 1,
							'settings' => array(
								'placeholder' => '',
								'maxLength'   => '',
								'multiline'   => 1,
								'initialRows' => 4,
							)
						)
					)
				),
				'newUserEmail' => array(
					'Content' => array(
						array(
							'name'         => 'HTML Email Body',
							'handle'       => 'exampleHtmlEmailBody',
							'instructions' => '',
							'type'         => 'RichText',
							'required'     => 1,
							'settings'     => array(
								'configFile'  => '',
								'cleanupHtml' => '1',
								'purifyHtml'  => '',
								'columnType'  => 'mediumtext'
							)
						),
						array(
							'name'     => 'Text Email Body',
							'handle'   => 'exampleTextEmailBody',
							'type'     => 'PlainText',
							'required' => 1,
							'settings' => array(
								'placeholder' => '',
								'maxLength'   => '',
								'multiline'   => 1,
								'initialRows' => 4,
							)
						)
					)
				),
			);

			foreach ($notificationEmails as $handle => $notificationEmail)
			{
				$fieldLayout    = array();
				$requiredFields = array();

				$tabs = $fieldSettings[$handle];

				// Ensure we have a Field Group to save our Fields
				if (!$sproutEmailFieldGroup = $this->createFieldGroup())
				{
					SproutEmailPlugin::log('Could not save the Sprout Email Examples field group.', LogLevel::Warning);

					craft()->userSession->setError(Craft::t('Unable to create examples. Field group not saved.'));

					return false;
				}

				foreach ($tabs as $tabName => $newFields)
				{
					foreach ($newFields as $newField)
					{
						if (!$field = craft()->fields->getFieldByHandle($newField['handle']))
						{
							$field           = new FieldModel();
							$field->groupId  = $sproutEmailFieldGroup->id;
							$field->name     = $newField['name'];
							$field->handle   = $newField['handle'];
							$field->type     = $newField['type'];
							$field->required = $newField['required'];
							$field->settings = $newField['settings'];

							// Save our field
							craft()->fields->saveField($field);
						}

						$fieldLayout[$tabName][] = $field->id;

						if ($field->required)
						{
							$requiredFields[] = $field->id;
						}
					}
				}

				// Set the field layout
				$fieldLayout = craft()->fields->assembleLayout($fieldLayout, $requiredFields);

				$fieldLayout->type = 'SproutEmail_NotificationEmail';

				$notification = new SproutEmail_NotificationEmailModel();

				$notification->setAttributes($notificationEmail);

				$notification->setFieldLayout($fieldLayout);

				// Remove previous field layout
				craft()->fields->deleteLayoutById($notification->fieldLayoutId);

				craft()->fields->saveLayout($fieldLayout);

				$notification->getContent()->title = $notificationEmail['title'];

				$notification->getContent()->exampleHtmlEmailBody = $notificationEmail['htmlBody'];

				$notification->getContent()->exampleTextEmailBody = $notificationEmail['textBody'];

				// need to pass post request for $event->prepareOptions() in saveNotification
				$_POST['rules'] = $notificationEmail['options'];

				sproutEmail()->notificationEmails->saveNotification($notification);
			}
		}
		catch (\Exception $e)
		{
			$this->handleError($e);
		}
	}

	/**
	 * @param $sproutEmailFieldGroup
	 *
	 * @return bool
	 */
	private function createFieldGroup()
	{
		$sproutEmailFieldGroup       = new FieldGroupModel();
		$sproutEmailFieldGroup->name = "Sprout Email Examples";

		if (craft()->fields->saveGroup($sproutEmailFieldGroup))
		{
			return $sproutEmailFieldGroup;
		}

		// If we couldn't save the group, try to find the ID of one that exists
		$existingFieldGroup = craft()->db->createCommand()
			->select('*')
			->from('fieldgroups')
			->where('name = :name', array(':name' => $sproutEmailFieldGroup->name))
			->queryRow();

		if ($existingFieldGroup)
		{
			return new FieldGroupModel($existingFieldGroup);
		}

		return false;
	}

	/**
	 * Handle installation errors
	 *
	 * @param Exception $exception
	 *
	 * @return void
	 */
	private function handleError($exception)
	{
		craft()->userSession->setError(Craft::t('Unable to install the examples.'));

		craft()->userSession->setError($exception->getMessage());

		$this->redirect('sproutemail/settings/examples');
	}
}