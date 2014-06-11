<?php
namespace Craft;

/**
 * Campaign model
 *
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
        return array(
        		
        	// campaign info
        	'id'				=> array(AttributeType::Number),
			'name'				=> array(AttributeType::String),
			'subject'			=> array(AttributeType::String),
			'fromName'			=> array(AttributeType::String),
			'fromEmail'			=> array(AttributeType::Email),
			'replyToEmail'		=> array(AttributeType::Email),
			'emailProvider'		=> array(AttributeType::String),
        	'notificationEvent'	=> array(AttributeType::Number),
        		
			// email template
			'templateOption'	=> array(AttributeType::Number),
			'htmlBody'			=> array(AttributeType::String),
			'textBody'			=> array(AttributeType::String),
			'sectionId'			=> array(AttributeType::Number),
			'htmlTemplate'		=> array(AttributeType::String),
			'textTemplate'		=> array(AttributeType::String),
        		
			// recipients        
        	'recipientOption'	=> array(AttributeType::Number),
			'emailProviderRecipientListId' => array(AttributeType::Enum),
			'recipients'		=> array(AttributeType::String),
            'useRecipientLists'	=> array(AttributeType::Number),
        		
        	// events
        	'notificationEvents'=> array(AttributeType::Enum),
        );
    }
}