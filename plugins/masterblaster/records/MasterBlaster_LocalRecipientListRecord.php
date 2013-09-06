<?php
namespace Craft;

class MasterBlaster_LocalRecipientListRecord extends BaseRecord
{	
	/**
	 * Return table name corresponding to this record
	 * @return string
	 */
	public function getTableName()
	{
		return 'masterblaster_local_recipient_lists';
	}

	/**
	 * These have to be explicitly defined in order for the plugin to install
	 * @return array
	 */
    public function defineAttributes()
    {
        return array(
        	'name' => array(AttributeType::String),
            'dateCreated' => array(AttributeType::DateTime),
            'dateUpdated' => array(AttributeType::DateTime),
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
    					self::HAS_ONE,
    					'MasterBlaster_RecipientListRecord',
    					'emailProviderRecipientListId'),
    			'localRecipientListAssignment' => array(
    					self::HAS_MANY,
    					'MasterBlaster_LocalRecipientListAssignmentRecord',
    					'localRecipientListId'),
    			'recipient' => array(
    					self::HAS_MANY,
    					'MasterBlaster_RecipientRecord',
    					'recipientId',
    					'through' => 'localRecipientListAssignment'
    			)
    	);
    }
}
