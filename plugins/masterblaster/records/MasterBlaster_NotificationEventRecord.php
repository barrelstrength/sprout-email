<?php
namespace Craft;

class MasterBlaster_NotificationEventRecord extends BaseRecord
{	
	/**
	 * Return table name corresponding to this record
	 * @return string
	 */
	public function getTableName()
	{
		return 'masterblaster_notification_events';
	}

	/**
	 * These have to be explicitly defined in order for the plugin to install
	 * @return array
	 */
    public function defineAttributes()
    {
        return array(
        	'event' => array(AttributeType::String),
        	'description' => array(AttributeType::String),
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
    			'campaignNotificationEvent' => array(
    					self::HAS_MANY,
    					'MasterBlaster_CampaignNotificationEventRecord',
    					'notificationEventId'),
    			'campaign' => array(
    					self::HAS_MANY,
    					'MasterBlaster_CampaignRecord',
    					'campaignId',
    					'through' => 'campaignNotificationEvent'
    			),
    	);
    }
}
