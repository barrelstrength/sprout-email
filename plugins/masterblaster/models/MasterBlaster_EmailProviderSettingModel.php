<?php
namespace Craft;

class MasterBlaster_EmailProviderSettingModel extends BaseModel
{

	/**
	 * These have to be explicitly defined in order for the plugin to install
	 * 
	 * @return array
	 */
    public function defineAttributes()
    {
        return array(
        	'id'				=> array(AttributeType::Number),
        	'emailProvider'		=> array(AttributeType::String),
            'name'				=> array(AttributeType::String),
        	'fromEmail'			=> array(AttributeType::Email),
        		
        	// apiSettings is a generic field to store email provider specific settings;
            'apiSettings'		=> array(AttributeType::String),         		
            
            'dateCreated'		=> array(AttributeType::DateTime),
            'dateUpdated'		=> array(AttributeType::DateTime),
        );
    }
}