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
		$this->_installExampleTemplates();
		$this->_installExampleData();

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
	private function _installExampleTemplates()
	{
		try
		{
			$fileHelper = new \CFileHelper();
			$path = craft()->path->getSiteTemplatesPath();

			$dir = @mkdir($path . 'sproutemail');

			$fileHelper->copyDirectory(craft()->path->getPluginsPath() . 'sproutemail/templates/_special/examples/emails',
					craft()->path->getSiteTemplatesPath() . 'sproutemail');

			$message = Craft::t("Example templates and entries has been created.");

			craft()->userSession->setNotice($message);

		}
		catch (\Exception $e)
		{

			$this->_handleError($e);
		}
	}

	/**
	 * Install example data
	 *
	 * @return void
	 */
	private function _installExampleData()
	{
		try
		{
			// Create Example Emails
			// ------------------------------------------------------------

			$emailSettings = array(
				array(
					'name' => 'Welcome Email - User Notification',
					'handle' => 'welcomeEmail',
					'type' => 'notification',
					'mailer' => 'defaultmailer',
					'hasUrls' => false,
					'urlFormat' => null,
					'hasAdvancedTitles' => false,
					'template' => 'sproutemail/notification',
					'templateCopyPaste' => null
				),
				array(
					'name' => 'New User - Admin Notification',
					'handle' => 'newUserEmail',
					'type' => 'notification',
					'mailer' => 'defaultmailer',
					'hasUrls' => false,
					'urlFormat' => null,
					'hasAdvancedTitles' => false,
					'template' => 'sproutemail/notification',
					'templateCopyPaste' => null
				),
				array(
					'name' => 'Monthly Newsletter',
					'handle' => 'monthlyNewsletter',
					'type' => 'email',
					'mailer' => 'copypaste',
					'hasUrls' => true,
					'hasAdvancedTitles' => false,
					'urlFormat' => 'sproutemail/{slug}',
					'template' => 'sproutemail/newsletter',
					'templateCopyPaste' => 'sproutemail/newsletter'
				),
			);

			$fieldSettings = array(
				'welcomeEmail' => array(
					'Content' => array(
						array(
			        'name' => 'HTML Email Body',
			        'handle' => 'exampleHtmlEmailBody',
			        'instructions' => '',
			        'type' => 'RichText',
							'required' => 1,
			        'settings' => array(
								'configFile' => '',
		            'cleanupHtml' => '1',
		            'purifyHtml' => '',
		            'columnType' => 'text'
							)
						),
						array(
							'name'     => 'Text Email Body',
							'handle'   => 'exampleTextEmailBody',
							'type'     => 'PlainText',
							'required' => 1,
							'settings' => array(
								'placeholder' => '',
								'maxLength' => '',
								'multiline' => 1,
								'initialRows' => 4,
							)
						)
					)
				),
				'newUserEmail' => array(
					'Content' => array(
						array(
							'name' => 'HTML Email Body',
							'handle' => 'exampleHtmlEmailBody',
							'instructions' => '',
							'type' => 'RichText',
							'required' => 1,
							'settings' => array(
								'configFile' => '',
								'cleanupHtml' => '1',
								'purifyHtml' => '',
								'columnType' => 'text'
							)
						),
						array(
							'name'     => 'Text Email Body',
							'handle'   => 'exampleTextEmailBody',
							'type'     => 'PlainText',
							'required' => 1,
							'settings' => array(
								'placeholder' => '',
								'maxLength' => '',
								'multiline' => 1,
								'initialRows' => 4,
							)
						)
					)
				),
				'monthlyNewsletter' => array(
					'Content' => array(
						array(
							'name' => 'HTML Email Body',
							'handle' => 'exampleHtmlEmailBody',
							'instructions' => '',
							'type' => 'RichText',
							'required' => 1,
							'settings' => array(
								'configFile' => '',
								'cleanupHtml' => '1',
								'purifyHtml' => '',
								'columnType' => 'text'
							)
						),
						array(
							'name'     => 'Text Email Body',
							'handle'   => 'exampleTextEmailBody',
							'type'     => 'PlainText',
							'required' => 1,
							'settings' => array(
								'placeholder' => '',
								'maxLength' => '',
								'multiline' => 1,
								'initialRows' => 4,
							)
						)
					)
				),
			);

			$currentUser = craft()->userSession->getUser();

			$emailExamples = array(
				'welcomeEmail' => array(
					'title' => 'Welcome!',
					'subjectLine' => 'Welcome!',
					'slug' => 'welcome',
					'uri' => null,
					'campaignId' => null,
					'sproutEmail' => array(
						'fromName' => craft()->getSiteName(),
						'fromEmail' => $currentUser->email,
						'replyTo' => $currentUser->email,
					),
					'recipient' => array(
						'onTheFlyRecipients' => '{email}',
					),
					'rules' => array(
						'craft' => array(
							'saveUser' => array(
								'whenNew' => '1',
								'whenUpdated' => '',
								'userGroupIds' => ''
							)
						),
					),
					'enabled' => true,
					'archived' => '0',
					'locale' => $currentUser->locale,
					'localeEnabled' => '1',
					'sent' => '0',
					'htmlBody' => '<p>Thanks for becoming a member.</p>
<ul>
	<li>Username: <strong>{username}</strong></li>
	<li>Email: <strong>{email}</strong></li>
</ul>',
					'textBody' => 'Thanks for becoming a member.

Username: {username}
Email: {email}',
				),
				'newUserEmail' => array(
					'title' => 'A new user has created an account',
					'subjectLine' => 'A new user has created an account',
					'slug' => 'a-new-user-has-created-an-account',
					'uri' => null,
					'campaignId' => null,
					'sproutEmail' => array(
						'fromName' => craft()->getSiteName(),
						'fromEmail' => $currentUser->email,
						'replyTo' => $currentUser->email,
					),
					'recipient' => array(
						'onTheFlyRecipients' => $currentUser->email,
					),
					'rules' => array(
						'craft' => array(
							'saveUser' => array(
								'whenNew' => '1',
                'whenUpdated' => '',
                'userGroupIds' => ''
              )
						)
					),
					'enabled' => true,
					'archived' => '0',
					'locale' => $currentUser->locale,
					'localeEnabled' => '1',
					'sent' => '0',
					'htmlBody' => '<p>A new user has been created:</p>
<ul>
	<li>Username: <strong>{username}</strong></li>
	<li>Email: <strong>{email}</strong></li>
</ul>',
					'textBody' => 'A new user has been created:

Username: {username}
Email: {email}',
				),
				'monthlyNewsletter' => array(
					'title' => 'Best Practices for your Email Subject Line',
					'subjectLine' => 'Best Practices for your Email Subject Line',
					'slug' => 'best-practices-for-your-email-subject-line',
					'uri' => 'sproutemail/best-practices-for-your-email-subject-line',
					'campaignId' => null,
					'sproutEmail' => array(
						'fromName' => craft()->getSiteName(),
						'fromEmail' => $currentUser->email,
						'replyTo' => $currentUser->email,
					),
					'recipient' => array(),
					'rules' => array(),
					'enabled' => true,
					'archived' => '0',
					'locale' => $currentUser->locale,
					'localeEnabled' => '1',
					'sent' => '0',
					'htmlBody' => '<p>Say something interesting!</p>',
					'textBody' => 'Say something interesting!',
				)
			);

			// Create Emails and their Content
			foreach ($emailSettings as $settings)
			{
				$campaign = new SproutEmail_CampaignModel();

				// Assign our email settings
				$campaign->name              = $settings['name'];
				$campaign->handle            = $settings['handle'];
				$campaign->type              = $settings['type'];
				$campaign->mailer            = $settings['mailer'];
				$campaign->hasUrls           = $settings['hasUrls'];
				$campaign->urlFormat         = $settings['urlFormat'];
				$campaign->hasAdvancedTitles = $settings['hasAdvancedTitles'];
				$campaign->template          = $settings['template'];
				$campaign->templateCopyPaste = $settings['templateCopyPaste'];

				// Only install our campaign example if CopyPaste Mailer is installed
				if ($campaign->mailer == 'copypaste' &&
					 !craft()->plugins->getPlugin('SproutEmailCopyPaste'))
				{
					break;
				}

				// Create the Email
				if (! $campaign = sproutEmail()->campaigns->saveCampaign($campaign))
				{
					SproutEmailPlugin::log('Campaign NOT CREATED');

					return false;
				}

				//------------------------------------------------------------

				// Do we have a new field that doesn't exist yet?
				// If so, save it and grab the id.

				$fieldLayout = array();
				$requiredFields = array();

				$tabs = $fieldSettings[$campaign->handle];

				// Ensure we have a Field Group to save our Fields
				if (!$sproutEmailFieldGroup = $this->_createFieldGroup())
				{
					SproutEmailPlugin::log('Could not save the Sprout Email Examples field group.', LogLevel::Warning);

					craft()->userSession->setError(Craft::t('Unable to create examples. Field group not saved.'));

					return false;
				}

				foreach ($tabs as $tabName => $newFields)
				{
					foreach ($newFields as $newField)
					{
						if (! $field = craft()->fields->getFieldByHandle($newField['handle']))
						{
							$field = new FieldModel();
							$field->groupId     = $sproutEmailFieldGroup->id;
							$field->name        = $newField['name'];
							$field->handle      = $newField['handle'];
							$field->type        = $newField['type'];
							$field->required    = $newField['required'];
							$field->settings    = $newField['settings'];

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

				$fieldLayout->type = 'SproutEmail_Campaign';
				$campaign->setFieldLayout($fieldLayout);

				// Save our email again with a layout
				sproutEmail()->campaigns->saveCampaign($campaign);

				$entryRecord = SproutEmail_EntryRecord::model()->findByAttributes(array('campaignId' => $campaign->id));

				if (! $entryRecord)
				{
					$entry = new SproutEmail_EntryModel();
				}
				else
				{
					$entry = SproutEmail_EntryModel::populateModel($entryRecord->getAttributes());
				}

				$entryData = $emailExamples[$campaign->handle];

				$_POST['sproutEmail']       = $entryData['sproutEmail'];
				$_POST['recipient']         = $entryData['recipient'];
				$_POST['rules']             = $entryData['rules'];

				unset($entryData['recipient']);
				unset($entryData['rules']);

				$entry->setAttributes($entryData);
				$entry->campaignId          = $campaign->id;
				$entry->fromName    = craft()->request->getPost('sproutEmail.fromName');
				$entry->fromEmail   = craft()->request->getPost('sproutEmail.fromEmail');
				$entry->replyTo     = craft()->request->getPost('sproutEmail.replyTo');

				$entry->getContent()->title = $entryData['title'];
				$entry->getContent()->exampleHtmlEmailBody = $entryData['htmlBody'];
				$entry->getContent()->exampleTextEmailBody = $entryData['textBody'];

				sproutEmail()->entries->saveEntry($entry, $campaign);

				if ($campaign->type == 'notification')
				{
					sproutEmail()->notifications->save('users-saveUser', $campaign->id);
				}
			}
		}
		catch (\Exception $e)
		{
			$this->_handleError($e);
		}
	}

	/**
	 * @param $sproutEmailFieldGroup
	 * @return bool
	 */
	private function _createFieldGroup()
	{
		$sproutEmailFieldGroup = new FieldGroupModel();
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
	 * @return void
	 */
	private function _handleError($exception)
	{
		craft()->userSession->setError(Craft::t('Unable to install the examples.'));
		$this->redirect('sproutemail/examples');
	}
}