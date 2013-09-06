<?php
namespace Craft;

class MasterBlaster_EmailProviderSettingRecord extends BaseRecord
{
	/**
	 * Corresponding table
	 * @return string
	 */
    public function getTableName()
    {
        return 'masterblaster_email_provider_settings';
    }
    
	/**
	 * These have to be explicitly defined in order for the plugin to install
	 * @return array
	 */
    public function defineAttributes()
    {
        return array(
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
