<?php
namespace Craft;

/**
 * EmailBlastType record
 */
class SproutEmail_EmailBlastTypeRecord extends BaseRecord
{
	public $rules = array ();
	public $sectionRecord;
	
	/**
	 * Return table name corresponding to this record
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'sproutemail_emailblasttypes';
	}
	
	/**
	 * These have to be explicitly defined in order for the plugin to install
	 *
	 * @return multitype:multitype:string multitype:boolean string
	 */
	public function defineAttributes()
	{
		return array (
			'fieldLayoutId' => array (
				AttributeType::Number
			),
			'sectionId' => array (
				AttributeType::Number 
			),
			'emailProvider' => array (
				AttributeType::String 
			),
			'name' => array (
				AttributeType::String 
			),
			'subject' => array (
				AttributeType::String 
			),
			'fromName' => array (
				AttributeType::String 
			),
			'fromEmail' => array (
				AttributeType::Email 
			),
			'replyToEmail' => array (
				AttributeType::Email 
			),

			'templateOption' => array (
				AttributeType::Number 
			),
			'htmlBody' => array (
				AttributeType::Mixed 
			),
			'textBody' => array (
				AttributeType::Mixed 
			),
			'subjectHandle' => array (
				AttributeType::String 
			),
			'htmlTemplate' => array (
				AttributeType::String 
			),
			'textTemplate' => array (
				AttributeType::String 
			),
			'htmlBodyTemplate' => array (
				AttributeType::String 
			),
			'textBodyTemplate' => array (
				AttributeType::String 
			),
			'recipients' => array (
				AttributeType::Mixed 
			),
			'dateCreated' => array (
				AttributeType::DateTime 
			),
			'dateUpdated' => array (
				AttributeType::DateTime 
			) 
		);
	}
	
	/**
	 * Record relationships
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		return array (
			'fieldLayout' => array(
				static::BELONGS_TO, 
				'FieldLayoutRecord', 
				'onDelete' => static::SET_NULL
			),
			'section' => array (
				self::BELONGS_TO,
				'SectionRecord',
				'sectionId' 
			),
			'emailBlastTypeRecipientList' => array (
				self::HAS_MANY,
				'SproutEmail_EmailBlastTypeRecipientListRecord',
				'emailBlastTypeId' 
			),
			'recipientList' => array (
				self::HAS_MANY,
				'SproutEmail_RecipientListRecord',
				'recipientListId',
				'through' => 'emailBlastTypeRecipientList' 
			),
			'emailBlastTypeNotificationEvent' => array (
				self::HAS_MANY,
				'SproutEmail_EmailBlastTypeNotificationEventRecord',
				'emailBlastTypeId' 
			),
			'notificationEvent' => array (
				self::HAS_MANY,
				'SproutEmail_NotificationEventRecord',
				'notificationEventId',
				'through' => 'emailBlastTypeNotificationEvent' 
			) 
		);
	}
	
	/**
	 * Function for adding rules
	 *
	 * @param array $rules            
	 * @return void
	 */
	public function addRules($rules = array())
	{
		$this->rules [] = $rules;
	}
	
	/**
	 * Yii style validation rules;
	 * These are the 'base' rules but specific ones are added in the service based on
	 * the scenario
	 *
	 * @return array
	 */
	public function rules()
	{
		$rules = array (
			// array('sectionId', 'exist'), // if section is passed, it must be a valid record
			array (
				'name,fromName,fromEmail,replyToEmail,templateOption',
				'required' 
			), // required fields
			array (
				'fromEmail',
				'email' 
			), // must be valid emails
			array (
				'emailProvider',
				'validEmailProvider' 
			)  // custom
		);
		
		return array_merge( $rules, $this->rules );
	}
	
	/**
	 * Custom email provider validator
	 *
	 * @param string $attr            
	 * @param array $params            
	 * @return void
	 */
	public function validEmailProvider($attr, $params)
	{
		if ( $this->{$attr} != 'SproutEmail' && ! array_key_exists( $this->{$attr}, craft()->sproutEmail_emailProvider->getEmailProviders() ) )
		{
			$this->addError( $attr, 'Invalid email provider.' );
		}
	}
	
	/**
	 * Return section based emailBlastType(s)
	 *
	 * @param int $emailBlastType_id            
	 * @return array EmailBlastTypes
	 */
	public function getEmailBlastTypes($emailBlastType_id = false)
	{
		$where_binds = 'templateOption=:templateOption';
		$where_params = array (
				':templateOption' => 3 
		);
		
		// @TODO - had a previously named function by the same name that did the 
		// commented out stuff... add back where?
		// if ( $emailBlastType_id )
		// {
		// 	$where_binds .= ' AND :id=mc.id';
		// 	$where_params [':id'] = $emailBlastType_id;
		// }
		
		// return craft()->db->createCommand()->select( 'mc.*,
		// 		el.slug as slug,
		// 		s.handle,
		// 		e.id as entryId,
		// 		c.title as title,
		// 		s.id as sectionId' )
		// 		->from( 'sproutemail_emailblasttypes mc' )
		// 		->leftJoin( 'sections s', 'mc.sectionId=s.id' )
		// 		->leftJoin( 'entries e', 's.id = e.sectionId' )
		// 		->leftJoin( 'content c', 'e.id = c.elementId' )
		// 		->leftJoin( 'elements_i18n el', 'e.id = el.elementId' )
		// 		->where( $where_binds, $where_params )
		// 		->order( 'mc.dateCreated desc, el.slug asc' )
		// 		->queryAll();

		return craft()->db->createCommand()
					->select( '*' )
					->from( 'sproutemail_emailblasttypes' )
					->where( 'templateOption=:templateOption', array( ':templateOption' => 3 ) )
					->order( 'dateCreated desc' )
					->queryAll();
	}

	/**
	 * Return section based emailBlastType(s) given entry id and emailBlastType id
	 *
	 * @param int $entryId            
	 * @param int $emailBlastTypeId            
	 * @return array EmailBlastTypes
	 */
	public function getEmailBlastTypeByEntryAndEmailBlastTypeId($entryId, $emailBlastTypeId)
	{
		$where_binds = 'mc.templateOption=:templateOption AND e.id=:entryId AND mc.id=:emailBlastTypeId';
		$where_params = array (
				':templateOption' => 3,
				':entryId' => $entryId,
				':emailBlastTypeId' => $emailBlastTypeId 
		);
		
		$res = craft()->db->createCommand()->select( 'mc.*,
				el.slug as slug,
				s.handle,
				c.title,
				e.id as entryId,
				s.id as sectionId' )->from( 'sproutemail_emailblasts mc' )->join( 'sections s', 'mc.sectionId=s.id' )->join( 'entries e', 's.id = e.sectionId' )->join( 'elements_i18n el', 'e.id = el.elementId' )->join( 'content c', 'el.elementId=c.elementId' )->where( $where_binds, $where_params )->queryRow();
		return $res;
	}
	
	/**
	 * Return section based emailBlastType(s) given entry id and emailBlastType id
	 *
	 * @param int $entryId            
	 * @param int $emailBlastTypeId            
	 * @return array EmailBlastTypes
	 */
	public function getEmailBlastTypeBySectionId($sectionId)
	{
		$res = craft()->db
		        ->createCommand()
		        ->from( 'sproutemail_emailblasts' )
		        ->where( array('sectionId' => $sectionId ) )
		        ->limit( 1 )
		        ->queryRow();
        return $res;
	}


	/**
	 * Returns notifications
	 *
	 * @return array
	 */
	public function getNotifications()
	{
		$res = craft()->db->createCommand()->select( 'mc.*,
				mcne.notificationEventId' )->from( 'sproutemail_emailblasts mc' )->join( 'sproutemail_emailblasttypes_notificationevents mcne', 'mc.id=mcne.emailBlastTypeId' )->queryAll();
		return $res;
	}
}
