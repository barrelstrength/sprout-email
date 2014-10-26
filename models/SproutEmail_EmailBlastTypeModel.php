<?php
namespace Craft;

/**
 * EmailBlastType model
 */
class SproutEmail_EmailBlastTypeModel extends BaseModel
{
	/**
	 * These have to be explicitly defined in order for the plugin to install
	 *
	 * @return array
	 */
	public function defineAttributes()
	{
		return array (
				
			// emailBlastType info
			'id' => array (
				AttributeType::Number 
			),
			'fieldLayoutId' => array (
				AttributeType::Number
			),
			'name' => array (
				AttributeType::String 
			),
			'handle' => array (
				AttributeType::String 
			),
			'titleFormat' => array (
				AttributeType::String
			),
			'hasUrls' => array(
				AttributeType::Bool, 
				'default' => true
			),
			'urlFormat' => array(
				AttributeType::String
			),
			'subject' => array (
				AttributeType::String 
			),
			'fromName' => array (
				AttributeType::String 
			),
			'fromEmail' => array (
				AttributeType::Email 
			),
			'replyToEmail' => array (
				AttributeType::Email 
			),
			'emailProvider' => array (
				AttributeType::String 
			),
			'notificationEvent' => array (
				AttributeType::Number 
			),
			
			// email template
			'templateOption' => array (
				AttributeType::Number 
			),
			'htmlBody' => array (
				AttributeType::String 
			),
			'textBody' => array (
				AttributeType::String 
			),
			'subjectHandle' => array (
				AttributeType::String 
			),
			'htmlTemplate' => array (
				AttributeType::String 
			),
			'textTemplate' => array (
				AttributeType::String 
			),
			'htmlBodyTemplate' => array (
				AttributeType::String 
			),
			'textBodyTemplate' => array (
				AttributeType::String 
			),
			
			// recipients
			'recipientOption' => array (
				AttributeType::Number 
			),
			'emailProviderRecipientListId' => array (
				AttributeType::Enum 
			),
			'recipients' => array (
				AttributeType::String 
			),
			'useRecipientLists' => array (
				AttributeType::Number 
			),
			
			// events
			'notificationEvents' => array (
				AttributeType::Enum 
			) 
		);
	}

	/**
	 * @return array
	 */
	public function behaviors()
	{
		return array(
			'fieldLayout' => new FieldLayoutBehavior('SproutEmail_EmailBlast'),
		);
	}

	public function getFieldLayout()
	{
		return $this->asa('fieldLayout')->getFieldLayout();
	}
	
	/**
	 * Returns the fields associated with this form.
	 *
	 * @return array
	 */
	public function getFields()
	{
		if (!isset($this->_fields))
		{
			$this->_fields = array();

			$fieldLayoutFields = $this->getFieldLayout()->getFields();

			foreach ($fieldLayoutFields as $fieldLayoutField)
			{
				$field = $fieldLayoutField->getField();
				$field->required = $fieldLayoutField->required;
				$this->_fields[] = $field;
			}
		}

		return $this->_fields;
	}

	/*
	 * Sets the fields associated with this form.
	 *
	 * @param array $fields
	 */
	public function setFields($fields)
	{
		$this->_fields = $fields;
	}

	/**
	 * Check if specified list is set in this model instance
	 *
	 * @param SproutEmail_RecipientListRecord $list            
	 */
	public function hasRecipientList(SproutEmail_RecipientListRecord $list)
	{
		if ( ! isset( $this->emailProviderRecipientListId [$list->type] ) || ! $this->emailProviderRecipientListId [$list->type])
			return false;
		
		foreach ( $this->emailProviderRecipientListId [$list->type] as $listId )
		{
			if ( $list->emailProviderRecipientListId == $listId )
				return true;
		}
				
		return false;
	}
}