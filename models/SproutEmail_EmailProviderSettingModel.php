<?php
namespace Craft;

/**
 * Email provider settings
 */
class SproutEmail_EmailProviderSettingModel extends BaseModel
{
	
	/**
	 * These have to be explicitly defined in order for the plugin to install
	 *
	 * @return array
	 */
	public function defineAttributes()
	{
		return array (
			'id'            => AttributeType::Number,
			'emailProvider' => AttributeType::String,
			
			// apiSettings is a generic field to store email provider specific settings;
			'apiSettings'   => AttributeType::String,
			
			'dateCreated'   => AttributeType::DateTime,
			'dateUpdated'   => AttributeType::DateTime 
		);
	}
}