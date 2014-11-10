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
		
		craft()->userSession->setNotice(Craft::t('Examples successfully installed.'));
		$this->redirect('sproutemail');
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
			@mkdir(craft()->path->getSiteTemplatesPath() . 'sproutemail');
			$fileHelper->copyDirectory(craft()->path->getPluginsPath() . 'sproutemail/templates/_special/examples/templates', craft()->path->getSiteTemplatesPath() . 'sproutemail');
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
			// Create Example Forms
			// ------------------------------------------------------------
			
			$user = craft()->userSession->getUser();

			$emailBlastTypeSettings = array(
				array(
					'name' => 'Marketing Email',
					'handle' => 'marketingemail',
					'subject' => 'Marketing Email Subject',
					'fromName' => craft()->getSiteName(),
					'fromEmail' => $user->email,
					'replyToEmail' => $user->email,
					'emailProvider' => 'SproutEmail',
					'template' => 'sproutemail/email-ink',
					'templateCopyPaste' => 'sproutemail/email-copypaste',
				),
				array(
					'name' => 'Email Newsletter',
					'handle' => 'newsletter',
					'subject' => 'Copy Paste Email Subject',
					'fromName' => craft()->getSiteName(),
					'fromEmail' => $user->email,
					'replyToEmail' => $user->email,
					'emailProvider' => 'CopyPaste',
					'template' => 'sproutemail/email-ink',
					'templateCopyPaste' => 'sproutemail/email-copypaste',
				),
			);

			$fieldSettings = array(
				'Section One' => array(
					array(
						'name'     => 'Plain Text Field',
						'handle'   => 'plainText',
						'type'     => 'PlainText',
						'required' => 1,
						'settings' => array(
							'placeholder' => '',
							'maxLength' => '',
							'multiline' => 0,
							'initialRows' => 4,
						)
					),
					array(
						'name'     => 'Dropdown Field',
						'handle'   => 'dropdown',
						'type'     => 'Dropdown',
						'required' => 1,
						'settings' => array(
							'options' => array(
								array(
									'label' => 'Option 1',
									'value' => 'option1',
									'default' => ''
								),
								array(
									'label' => 'Option 2',
									'value' => 'option2',
									'default' => ''
								),
								array(
									'label' => 'Option 3',
									'value' => 'option3',
									'default' => ''
								)
							)
						)
					),
					array(
						'name'     => 'Number Field',
						'handle'   => 'number',
						'type'     => 'Number',
						'required' => 0,
						'settings' => array(
							'min' => 0,
							'max' => '',
							'decimals' => ''
						)
					)
				),
				'Section Two' => array(
					array(
						'name'     => 'Radio Buttons Field',
						'handle'   => 'radioButtons',
						'type'     => 'RadioButtons',
						'required' => 0,
						'settings' => array(
							'options' => array(
								array(
									'label' => 'Option 1',
									'value' => 'option1',
									'default' => ''
								),
								array(
									'label' => 'Option 2',
									'value' => 'option2',
									'default' => ''
								),
								array(
									'label' => 'Option 3',
									'value' => 'option3',
									'default' => ''
								)
							)
						)
					),
					array(
						'name'     => 'Checkboxes Field',
						'handle'   => 'checkboxes',
						'type'     => 'Checkboxes',
						'required' => 0,
						'settings' => array(
							'options' => array(
								array(
									'label' => 'Option 1',
									'value' => 'option1',
									'default' => ''
								),
								array(
									'label' => 'Option 2',
									'value' => 'option2',
									'default' => ''
								),
								array(
									'label' => 'Option 3',
									'value' => 'option3',
									'default' => ''
								)
							)
						)
					),
					array(
						'name'     => 'Multi-select Field',
						'handle'   => 'multiSelect',
						'type'     => 'MultiSelect',
						'required' => 0,
						'settings' => array(
							'options' => array(
								array(
									'label' => 'Option 1',
									'value' => 'option1',
									'default' => ''
								),
								array(
									'label' => 'Option 2',
									'value' => 'option2',
									'default' => ''
								),
								array(
									'label' => 'Option 3',
									'value' => 'option3',
									'default' => ''
								)
							)
						)
					),
					array(
						'name'     => 'Textarea Field',
						'handle'   => 'textarea',
						'type'     => 'PlainText',
						'required' => 0,
						'settings' => array(
							'placeholder' => '',
							'maxLength' => '',
							'multiline' => 1,
							'initialRows' => 4,
						)
					)
				)
			);
		
			// Create Forms and their Content Tables
			foreach ($emailBlastTypeSettings as $settings) 
			{
				$emailBlastType = new SproutEmail_EmailBlastTypeModel();
				
				// Assign our Email Blast Type settings
				$emailBlastType->name = $settings['name'];
				$emailBlastType->handle = $settings['handle'];
				$emailBlastType->subject = $settings['subject'];
				$emailBlastType->fromName = $settings['fromName'];
				$emailBlastType->fromEmail = $settings['fromEmail'];
				$emailBlastType->replyToEmail = $settings['replyToEmail'];
				$emailBlastType->emailProvider = $settings['emailProvider'];
				$emailBlastType->template = $settings['template'];
				$emailBlastType->templateCopyPaste = $settings['templateCopyPaste'];

				// Create the Email Blast Type
				$emailBlastTypeId = craft()->sproutEmail_emailBlastType->saveEmailBlastType($emailBlastType);

				// Assign the id of what we just created to our model
				$emailBlastType->id = $emailBlastTypeId;

				// Save this again for the templates
				craft()->sproutEmail_emailBlastType->saveEmailBlastType($emailBlastType, 'template');

				//------------------------------------------------------------

				// Do we have a new field that doesn't exist yet?  
				// If so, save it and grab the id.

				$fieldLayout = array();
				$requiredFields = array();

				$tabs = $fieldSettings;

				foreach ($tabs as $tabName => $newFields) 
				{	
					foreach ($newFields as $newField) 
					{
						$field = new FieldModel();
						$field->name        = $newField['name'];
						$field->handle      = $newField['handle'];
						$field->type        = $newField['type'];
						$field->required    = $newField['required'];
						$field->settings    = $newField['settings'];

						// Check if we already have a field by this handle
						// If we do, just grab the ID
						// @TODO - should we namespace these so they 
						// are unlikely to collide?  Or does it matter?
						
						if ($existingField = craft()->fields->getFieldByHandle($field->handle)) 
						{
							$fieldId = $existingField->id;
						}
						else
						{
							// Save our field and establish a new field ID
							craft()->fields->saveField($field);
							$fieldId = $field->id;
						}
						
						$fieldLayout[$tabName][] = $fieldId;
						
						if ($field->required) 
						{
							$requiredFields[] = $fieldId;
						}
					}
				}

				// Set the field layout
				$fieldLayout = craft()->fields->assembleLayout($fieldLayout, $requiredFields);
				
				$fieldLayout->type = 'SproutEmail_EmailBlastType';
				$emailBlastType->setFieldLayout($fieldLayout);

				// Save our form again with a layout
				craft()->sproutEmail_emailBlastType->saveEmailBlastType($emailBlastType);
			}
		}
		catch (\Exception $e)
		{	
			$this->_handleError($e);
		}
	}
	
	/**
	 * Handle installation errors
	 * 
	 * @param Exception $exception
	 * @return void
	 */
	private function _handleError($exception)
	{
		SproutEmailPlugin::log("Error: Unable to install the examples. " . json_encode($exception));

		craft()->userSession->setError(Craft::t('Unable to install the examples.'));
		
		$this->redirect('sproutemail/examples');
	}
}