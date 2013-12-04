<?php
namespace Craft;

/**
 * Local recipient list assignment (to campaigns) record
 *
 */
class SproutEmail_LocalRecipientListAssignmentRecord extends BaseRecord
{	
	/**
	 * Return table name corresponding to this record
	 * @return string
	 */
	public function getTableName()
	{
		return 'sproutemail_local_recipient_list_assignment';
	}

	/**
	 * These have to be explicitly defined in order for the plugin to install
	 * @return array
	 */
    public function defineAttributes()
    {
        return array(
            'recipientId' => array(AttributeType::Number),
        	'localRecipientListId' => array(AttributeType::Number),
            'dateCreated' => array(AttributeType::DateTime),
            'dateUpdated' => array(AttributeType::DateTime),
        );
    }

}
