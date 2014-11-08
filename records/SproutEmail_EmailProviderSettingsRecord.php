<?php
namespace Craft;

/**
 * Email provider settings record
 */
class SproutEmail_EmailProviderSettingsRecord extends BaseRecord
{
	/**
	 * Corresponding table
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'sproutemail_email_provider_settings';
	}
	
	/**
	 * These have to be explicitly defined in order for the plugin to install
	 *
	 * @return array
	 */
	public function defineAttributes()
	{
		return array (
			'emailProvider' => AttributeType::String,
			
			// apiSettings is a generic field to store email provider specific settings;
			'apiSettings' => AttributeType::String,
			
			'dateCreated' => AttributeType::DateTime,
			'dateUpdated' => AttributeType::DateTime 
		);
	}
}
