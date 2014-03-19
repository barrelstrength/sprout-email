<?php
namespace Craft;

/**
 * Campaign record
 *
 */
class SproutEmail_CampaignRecord extends BaseRecord
{
	public $rules = array();
	public $sectionRecord;
	
	/**
	 * Return table name corresponding to this record
	 * @return string
	 */
	public function getTableName()
	{
		return 'sproutemail_campaigns';
	}
	
	/**
	 * These have to be explicitly defined in order for the plugin to install
	 * @return multitype:multitype:string  multitype:boolean string
	 */
    public function defineAttributes()
    {
        return array(
        	'sectionId'       => array(AttributeType::Number),
        	'emailProvider'   => array(AttributeType::String),
            'name'            => array(AttributeType::String),
            'subject'         => array(AttributeType::String),
            'fromName'        => array(AttributeType::String),
            'fromEmail'       => array(AttributeType::Email),
            'replyToEmail'    => array(AttributeType::Email),
        		
        	'templateOption'  => array(AttributeType::Number),
            'htmlBody'        => array(AttributeType::String),
            'textBody'        => array(AttributeType::String),
            'htmlTemplate'    => array(AttributeType::String),
            'textTemplate'    => array(AttributeType::String),
        		
        	'recipients'      => array(AttributeType::String),
        		
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
    			'section' => array(self::BELONGS_TO, 'SectionRecord', 'sectionId'),
    			'campaignRecipientList' => array(
    					self::HAS_MANY,
    					'SproutEmail_CampaignRecipientListRecord',
    					'campaignId'),
    			'recipientList' => array(
    					self::HAS_MANY,
    					'SproutEmail_RecipientListRecord',
    					'recipientListId',
    					'through' => 'campaignRecipientList'
    			),
    			'campaignNotificationEvent' => array(
    					self::HAS_MANY,
    					'SproutEmail_CampaignNotificationEventRecord',
    					'campaignId'
    			),
    			'notificationEvent' => array(
    					self::HAS_MANY,
    					'SproutEmail_NotificationEventRecord',
    					'notificationEventId',
    					'through' => 'campaignNotificationEvent'
    			)
    	);
    }
    
    /**
     * Function for adding rules
     * @param array $rules
     * @return void
     */
    public function addRules($rules = array())
    {
    	$this->rules[] = $rules;
    }
    
    /**
     * Yii style validation rules;
     * These are the 'base' rules but specific ones are added in the service based on
     * the scenario
     * @return array
     */
    public function rules()
    {
    	$rules = array(
    		//array('sectionId', 'exist'), // if section is passed, it must be a valid record
    		array('name,subject,fromName,fromEmail,replyToEmail,templateOption', 'required'), // required fields
    		array('fromEmail', 'email'), // must be valid emails
    		array('emailProvider', 'validEmailProvider') // custom
    	);
    	    	
    	return array_merge($rules, $this->rules);
    }
    
    /**
     * Custom email provider validator
     * @param string $attr
     * @param array $params
     * @return void
     */
	public function validEmailProvider($attr, $params)
	{
		if($this->{$attr} != 'SproutEmail' && ! array_key_exists($this->{$attr}, craft()->sproutEmail_emailProvider->getEmailProviders()))
		{
			$this->addError($attr, 'Invalid email provider.');
		}
	}
	
	/**
	 * Return section based campaign(s)
	 * @param int $campaign_id
	 * @return array Campaigns
	 */
	public function getSectionBasedCampaigns($campaign_id = false)
	{
		$where_binds = 'templateOption=:templateOption';
		$where_params = array(':templateOption' => 3);
		
		if($campaign_id)
		{
			$where_binds .= ' AND :id=mc.id';
			$where_params[':id'] = $campaign_id;
		}
		
		return craft()->db->createCommand()
		->select('mc.*,
				el.slug as slug,
				s.handle,
				e.id as entryId,
				s.id as sectionId')
		->from('sproutemail_campaigns mc')
		->join('sections s', 'mc.sectionId=s.id')
		->join('entries e', 's.id = e.sectionId')
		->join('elements_i18n el', 'e.id = el.elementId')
		->where($where_binds, $where_params)
		->order('mc.dateCreated desc, el.slug asc')
		->queryAll();
	}
	
	/**
	 * Return section based campaign(s) given entry id and campaign id
	 * @param int $entryId
	 * @param int $campaignId
	 * @return array Campaigns
	 */
	public function getSectionBasedCampaignByEntryAndCampaignId($entryId, $campaignId)
	{
		$where_binds = 'mc.templateOption=:templateOption AND e.entryId=:entryId AND mc.id=:campaignId';
		$where_params = array(':templateOption' => 3, ':entryId' => $entryId, ':campaignId' => $campaignId);
	
		$res = craft()->db->createCommand()
		->select('mc.*,
				el.slug as slug,
				s.handle,
				e.id as entryId,
				s.id as sectionId')
		->from('sproutemail_campaigns mc')
		->join('sections s', 'mc.sectionId=s.id')
		->join('entries e', 's.id = e.sectionId')
		->join('elements_i18n el', 'e.id = el.elementId')
		->where($where_binds, $where_params)
		->queryRow();
		return $res;
	}
	
	/**
	 * Returns notifications
	 * @return array
	 */
	public function getNotifications()
	{
		$res = craft()->db->createCommand()
		->select('mc.*,
				mcne.notificationEventId')
		->from('sproutemail_campaigns mc')
		->join('sproutemail_campaign_notification_events mcne', 'mc.id=mcne.campaignId')
		->queryAll();
		return $res;
	}
}
