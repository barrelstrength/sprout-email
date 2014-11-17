<?php
namespace Craft;

/**
 * Campaign recipient list record
 */
class SproutEmail_CampaignRecipientListRecord extends BaseRecord
{
	/**
	 * Return table name corresponding to this record
	 * 
	 * @return string
	 */
	public function getTableName()
	{
		return 'sproutemail_campaigns_recipientlists';
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
			'campaignId' => AttributeType::Number,
			'dateCreated'      => AttributeType::DateTime,
			'dateUpdated'      => AttributeType::DateTime 
		);
	}
}
