<?php
namespace Craft;

class MasterBlaster_RecipientListRecord extends BaseRecord
{	
	/**
	 * Return table name corresponding to this record
	 * @return string
	 */
	public function getTableName()
	{
		return 'masterblaster_recipient_lists';
	}

	/**
	 * These have to be explicitly defined in order for the plugin to install
	 * @return array
	 */
    public function defineAttributes()
    {
        return array(
        	'emailProviderRecipientListId' => array(AttributeType::String),
        	'emailProvider'   => array(AttributeType::String),
            'dateCreated'     => array(AttributeType::DateTime),
            'dateUpdated'     => array(AttributeType::DateTime),
        );
    }

    /**
     * Record relationships
     * @return array
     */
    public function defineRelations()
    {
    	return array(
    			'campaignRecipientList' => array(
    					self::HAS_MANY,
    					'MasterBlaster_CampaignRecipientListRecord',
    					'recipientListId'),
    			'campaign' => array(
    					self::HAS_MANY,
    					'MasterBlaster_CampaignRecord',
    					'campaignId',
    					'through' => 'campaignRecipientList'
    			),
    			'localRecipientList' => array(
    					self::HAS_ONE,
    					'MasterBlaster_LocalRecipientListRecord',
    					'emailProviderRecipientListId'),
    	);
    }
}
