<?php
namespace Craft;

/**
 * EmailBlastType recipient list record
 */
class SproutEmail_EmailBlastTypeRecipientListRecord extends BaseRecord
{
	/**
	 * Return table name corresponding to this record
	 * 
	 * @return string
	 */
	public function getTableName()
	{
		return 'sproutemail_emailblasttypes_recipientlists';
	}
	
	/**
	 * These have to be explicitly defined in order for the plugin to install
	 * 
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'recipientListId'  => AttributeType::Number,
			'emailBlastTypeId' => AttributeType::Number,
			'dateCreated'      => AttributeType::DateTime,
			'dateUpdated'      => AttributeType::DateTime 
		);
	}
}
