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
			'fieldLayoutId'    => AttributeType::Number,
			'emailProvider'    => AttributeType::String,
			'name'             => AttributeType::String,
			'handle'           => AttributeType::String,
			'type'             => array(
														AttributeType::Enum, 
														'values' => array(
															EmailBlastType::EmailBlast, 
															EmailBlastType::Notification
														)),
			'titleFormat'      => AttributeType::String,
			'hasUrls'          => array(
															AttributeType::Bool, 
															'default' => true,
														),
			'hasAdvancedTitles' => array(
															AttributeType::Bool, 
															'default' => true,
														 ),
			'subject'          => AttributeType::String,
			'fromName'         => AttributeType::String,
			'fromEmail'        => AttributeType::Email,
			'replyToEmail'     => AttributeType::Email,

			'template'         => AttributeType::String,
			'templateCopyPaste'=> AttributeType::String,
			
			'recipients'       => AttributeType::Mixed,
			'dateCreated'      => AttributeType::DateTime,
			'dateUpdated'      => AttributeType::DateTime 
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
			array (
				'name,fromName,fromEmail,replyToEmail',
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
}
