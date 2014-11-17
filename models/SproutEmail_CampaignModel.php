<?php
namespace Craft;

/**
 * Campaign model
 */
class SproutEmail_CampaignModel extends BaseModel
{
	/**
	 * These have to be explicitly defined in order for the plugin to install
	 *
	 * @return array
	 */
	public function defineAttributes()
	{
		return array (
				
			// campaign info
			'id'                => AttributeType::Number,
			'fieldLayoutId'     => AttributeType::Number,
			'name'              => AttributeType::String,
			'handle'            => AttributeType::String ,
			'type'              => array(
															AttributeType::Enum, 
															'values' => array(
																Campaign::Email, 
																Campaign::Notification
															)),
			'titleFormat'       => AttributeType::String,
			'hasUrls'           => array(
															AttributeType::Bool, 
															'default' => true,
														 ),
			'hasAdvancedTitles' => array(
															AttributeType::Bool, 
															'default' => true,
														 ),
			'urlFormat'         => AttributeType::String,
			'subject'           => AttributeType::String,
			'fromName'          => AttributeType::String,
			'fromEmail'         => AttributeType::Email,
			'replyToEmail'      => AttributeType::Email,
			'emailProvider'     => AttributeType::String,
			'notificationEvent' => AttributeType::Number,
			'dateUpdated'        => AttributeType::DateTime,
			
			// email template
			'template'          => AttributeType::String,
			'templateCopyPaste' => AttributeType::String,
			
			// recipients
			'recipientOption'   => AttributeType::Number,
			'emailProviderRecipientListId' => AttributeType::Enum,
			'recipients'        => AttributeType::String,
			'useRecipientLists' => AttributeType::Number,
			
			// events
			'notificationEvents' => AttributeType::Enum,
			
		);
	}

	/**
	 * @return array
	 */
	public function behaviors()
	{
		return array(
			'fieldLayout' => new FieldLayoutBehavior('SproutEmail_Entry'),
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