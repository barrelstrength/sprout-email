<?php
namespace Craft;

/**
 * EmailBlastType notification event record
 */
class SproutEmail_EmailBlastTypeNotificationEventRecord extends BaseRecord
{
	/**
	 * Return table name corresponding to this record
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'sproutemail_emailblasttypes_notificationevents';
	}
	
	/**
	 * These have to be explicitly defined in order for the plugin to install
	 *
	 * @return array
	 */
	public function defineAttributes()
	{
		return array (
			'notificationEventId' => array (
				AttributeType::Number 
			),
			'emailBlastTypeId' => array (
				AttributeType::Number 
			),
			'options' => array (
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
				'notificationEvent' => array (
						self::BELONGS_TO,
						'SproutEmail_NotificationEventRecord',
						'notificationEventId'
				)
		);
	}
	
	/**
	 * Associates a notification emailBlastType with an event
	 *
	 * @param int $emailBlastTypeId            
	 * @param int $notificationEventId            
	 */
	public function associateEmailBlastTypeEvent($emailBlastTypeId, $notificationEventId)
	{
		// try to get it, if exists, update else create new
		$criteria = new \CDbCriteria();
		$criteria->condition = 'emailBlastTypeId=:emailBlastTypeId';
		$criteria->params = array (
				':emailBlastTypeId' => $emailBlastTypeId 
		);
		if ( ! $emailBlastTypeNotification = SproutEmail_EmailBlastTypeNotificationEventRecord::model()->find( $criteria ) )
		{
			$emailBlastTypeNotification = new SproutEmail_EmailBlastTypeNotificationEventRecord();
		}
		
		$emailBlastTypeNotification->emailBlastTypeId = $emailBlastTypeId;
		$emailBlastTypeNotification->notificationEventId = $notificationEventId;
		return $emailBlastTypeNotification->save( false );
	}
	
	/**
	 * Returns emailblasts which meet the event and event options criteria
	 *
	 * @param string $event            
	 * @param obj $entry            
	 * @return array of SproutEmail_EmailBlastTypeRecord objects
	 */
	public function getEmailBlastTypeEventNotifications($event, $entry)
	{
		$criteria = new \CDbCriteria();
		$criteria->condition = 'event=:event';
		$criteria->params = array (
				':event' => $event 
		);
		
		$res = SproutEmail_NotificationEventRecord::model()->with( 'emailBlastType', 'emailBlastType.recipientList', 'emailBlastTypeNotificationEvent' )->find( $criteria );
		
		if ( ! isset( $res->emailBlastType ) || ! $res->emailBlastType )
		{
			return false;
		}
		
		$notificationEmailBlastTypeIds = array ();
		
		// these match the event; now we need to narrow down by event options
		foreach ( $res->emailBlastTypeNotificationEvent as $key => $emailBlastTypeNotification )
		{
			$notificationEmailBlastTypeIds [$key] = $emailBlastTypeNotification->emailBlastTypeId; // assume it's a match
			if ( $opts = $emailBlastTypeNotification->options ) // get options, if any
			{
				foreach ( $opts ['options'] as $option_key => $option ) // process each option set associated with the campagin
				{
					if ( ! is_array( $option ) )
					{
						continue;
					}
					
					switch ($option_key)
					{
						case 'userGroupIds' :
							
							// process 'on user save' type events
							if ( $entry->elementType == 'User' )
							{
								$groups_arr = array ();
								if ( $groups = craft()->userGroups->getGroupsByUserId( $entry->id ) )
								{
									foreach ( $groups as $group )
									{
										if ( ! in_array( $group->id, $option ) )
										{
											unset( $notificationEmailBlastTypeIds [$key] );
											continue;
										}
									}
								}
								else
								{
									unset( $notificationEmailBlastTypeIds [$key] );
									continue;
								}
							}
							
							// process 'on content save' type events
							else if ( strpos( get_class( $entry ), 'EntryModel' ) !== false )
							{
								
								if ( ! in_array( $entry->sectionId, $option ) )
								{
									unset( $notificationEmailBlastTypeIds [$key] );
									continue;
								}
							}
							break;
						case 'sectionIds' :
							
							// process 'on content save' type events
							if ( strpos( get_class( $entry ), 'EntryModel' ) !== false )
							{
								if ( ! in_array( $entry->sectionId, $option ) )
								{
									unset( $notificationEmailBlastTypeIds [$key] );
									continue;
								}
							}
							break;
					}
				}
			}
		}
		
		// compile an array of emailBlastType objects and return
		$notificationEmailBlastTypes = array ();
		foreach ( $res->emailBlastType as $emailBlastType )
		{
			if ( in_array( $emailBlastType->id, $notificationEmailBlastTypeIds ) )
			{
				$notificationEmailBlastTypes [] = $emailBlastType;
			}
		}
		
		return $notificationEmailBlastTypes;
	}
	
	/**
	 * Sets an event and event options for specified emailBlastType
	 *
	 * @param int $emailBlastTypeId            
	 * @param array $data            
	 * @return bool
	 */
	public function setEmailBlastTypeNotificationEventOptions($emailBlastTypeId, $data)
	{
		// handle options
		$options = array ();
		switch ($data ['notificationEvent'])
		{
			case 1 : // entries.saveEntry
				$options = array (
						'options' => array (
								'sectionIds' => $data ['entriesSaveEntryNewSectionIds'] 
						) 
				);
				break;
			case 4 : // users.saveProfile
				$options = array (
						'options' => array (
								
								'userGroupIds' => $data ['usersSaveProfileUseroptIds'] 
						) 
				);
				break;
			case 3 : // users.saveUser
				$options = array (
						'options' => array (
								'userGroupIds' => $data ['usersSaveUserUseroptIds'] 
						) 
				);
				break;
			case 2 : // content.saveContent
				$options = array (
						'options' => array (
								'sectionIds' => $data ['entriesSaveEntrySectionIds'] 
						) 
				);
				break;
			case 17:
				$options = array (
						'options' => array (
								'cronHash' => $data ['cronHash']
						)
				);
				break;
			default :
				$options = array (
						'options' => isset( $data ['options'] ) && $data ['options'] ? $data ['options'] : array () 
				);
				break;
		}
		
		$criteria = new \CDbCriteria();
		$criteria->condition = 'emailBlastTypeId=:emailBlastTypeId';
		$criteria->params = array (
				':emailBlastTypeId' => $emailBlastTypeId 
		);
		if ( ! $emailBlastTypeNotification = SproutEmail_EmailBlastTypeNotificationEventRecord::model()->find( $criteria ) )
		{
			return false;
		}
		
		$emailBlastTypeNotification->options = $options;
		return $emailBlastTypeNotification->save( false );
	}
}
