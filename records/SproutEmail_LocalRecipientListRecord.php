<?php
namespace Craft;

/**
 * Local recipient list record
 *
 */
class SproutEmail_LocalRecipientListRecord extends BaseRecord
{	
	/**
	 * Return table name corresponding to this record
	 * @return string
	 */
	public function getTableName()
	{
		return 'sproutemail_local_recipient_lists';
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
    					'SproutEmail_RecipientListRecord',
    					'emailProviderRecipientListId'),
    			'localRecipientListAssignment' => array(
    					self::HAS_MANY,
    					'SproutEmail_LocalRecipientListAssignmentRecord',
    					'localRecipientListId'),
    			'recipient' => array(
    					self::HAS_MANY,
    					'SproutEmail_RecipientRecord',
    					'recipientId',
    					'through' => 'localRecipientListAssignment'
    			)
    	);
    }
}
