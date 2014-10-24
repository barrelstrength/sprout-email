<?php

namespace Craft;

/**
 * Main SproutEmail service
 */
class SproutEmailService extends BaseApplicationComponent
{
	/**
	 * Returns all emailblasts.
	 *
	 * @param string|null $indexBy            
	 * @return array
	 */
	public function getAllEmailBlastTypes()
	{
		$criteria = new \CDbCriteria();
		$criteria->order = 'dateCreated DESC';
		return SproutEmail_EmailBlastTypeRecord::model()->findAll( $criteria );
	}
	
	/**
	 * Returns all EmailBlastType Info (just settings, not related entries).
	 *
	 * @return object emailBlastType table records
	 *        
	 * @todo - Need a better way to identify between EmailBlastTypes
	 *       and Notifications: This is not clear and won't be true
	 *       when we have native EmailBlastTypes: where('emailProvider != "SproutEmail"')
	 */
	public function getAllEmailBlastTypeInfo()
	{
		$query = craft()->db->createCommand()->from( 'sproutemail_emailblasts' )->queryAll();
		
		return $query;
	}
	
	/**
	 * Returns emailBlastTypeRecipient lists
	 *
	 * @param int $emailBlastTypeId            
	 * @return array
	 */
	public function getEmailBlastTypeRecipientLists($emailBlastTypeId)
	{
		$criteria = new \CDbCriteria();
		$criteria->condition = 'emailBlastType.id=:emailBlastTypeId';
		$criteria->params = array (
				':emailBlastTypeId' => $emailBlastTypeId 
		);
		
		return SproutEmail_RecipientListRecord::model()->with( 'emailBlastType' )->findAll( $criteria );
	}
	
	/**
	 * Returns all section based emailblasts.
	 *
	 * @param string|null $indexBy            
	 * @return array
	 */
	public function getEmailBlastTypeById($emailBlastTypeId)
	{
		$emailBlastTypeRecord = SproutEmail_EmailBlastTypeRecord::model();
		$emailBlastTypeRecord = $emailBlastTypeRecord->findById($emailBlastTypeId);
		
		if ($emailBlastTypeRecord) 
		{
			return SproutEmail_EmailBlastTypeModel::populateModel($emailBlastTypeRecord);
		} 
		else 
		{
			return null;
		}

	/**
	 * Returns all section based emailblasts.
	 * return SproutEmail_EmailBlastTypeRecord::model()->getEmailBlastTypes();
	 */
	
		// return SproutEmail_EmailBlastTypeRecord::model()->getEmailBlastTypes( $emailBlastType_id );
	}

	
	
	/**
	 * Returns section based emailBlastType by entryId
	 *
	 * @param string|null $entryId            
	 * @return array
	 */
	public function getEmailBlastTypeByEntryAndEmailBlastTypeId($entryId = false, $emailBlastTypeId = false)
	{
		return SproutEmail_EmailBlastTypeRecord::model()->getEmailBlastTypeByEntryAndEmailBlastTypeId( $entryId, $emailBlastTypeId );
	}
	
	/**
	 * Gets a emailBlastType
	 *
	 * @param
	 *            array possible conditions: array('id' => <id>, 'handle' => <handle>, ...)
	 *            as defined in $valid_keys
	 * @return SproutEmail_EmailBlastTypeModel null
	 */
	public function getEmailBlastType($conditions = array())
	{
		// we can do where clauses on these keys only
		$valid_keys = array (
			'id',
			'handle' 
		);
		
		$criteria = new \CDbCriteria();
		
		if ( ! empty( $conditions ) )
		{
			$params = array ();
			foreach ( $conditions as $key => $val )
			{
				// only accept our defined keys
				if ( ! in_array( $key, $valid_keys ) )
				{
					continue;
				}
				
				$criteria->addCondition( 't.' . $key . '=:' . $key );
				$params [':' . $key] = $val;
			}
			
			if ( ! empty( $params ) )
			{
				$criteria->params = $params;
			}
		}
		
		// get emailBlastType record with recipient lists
		$emailBlastTypeRecord = SproutEmail_EmailBlastTypeRecord::model()->with( 'recipientList', 'emailBlastTypeNotificationEvent' )->find( $criteria );
		
		if ( $emailBlastTypeRecord )
		{
			// now we need to populate the model
			$emailBlastTypeModel = SproutEmail_EmailBlastTypeModel::populateModel( $emailBlastTypeRecord );
			
			$unserialized = array ();
			foreach ( $emailBlastTypeRecord->emailBlastTypeNotificationEvent as $event )
			{
				$opts = $event->options;
				$event->options = isset( $opts ['options'] ) ? $opts ['options'] : array ();
				$unserialized [] = $event;
			}
			
			$emailBlastTypeModel->notificationEvents = $unserialized;
			
			// now for the recipient related data
			if ( count( $emailBlastTypeRecord->recipientList ) > 0 )
			{
				$emailProviderRecipientListIdArr = array ();
				foreach ( $emailBlastTypeRecord->recipientList as $list )
				{
					$emailProviderRecipientListIdArr [$list->emailProviderRecipientListId] = $list->emailProviderRecipientListId;
				}
				
				$emailBlastTypeModel->emailProviderRecipientListId = $emailProviderRecipientListIdArr;
			}
			
			return $emailBlastTypeModel;
		}
	}
	
	/**
	 * Save event
	 *
	 * @param SproutEmail_NotificationEventModel $event            
	 * @throws Exception
	 * @return SproutEmail_NotificationEventModel \Craft\SproutEmail_NotificationEventRecord
	 */
	public function saveEvent(SproutEmail_NotificationEventModel &$event)
	{
		if ( isset( $event->id ) && $event->id )
		{
			$eventRecord = SproutEmail_NotificationEventRecord::model()->findById( $event->id );
			
			if ( ! $eventRecord )
			{
				throw new Exception( Craft::t( 'No event exists with the ID “{id}”', array (
						'id' => $event->id 
				) ) );
			}
		}
		else
		{
			$eventRecord = new SproutEmail_NotificationEventRecord();
		}
		
		$eventRecord->registrar = $event->registrar;
		$eventRecord->event = $event->event;
		$eventRecord->description = $event->description;
		
		$eventRecord->validate();
		$event->addErrors( $eventRecord->getErrors() );
		
		if ( ! $eventRecord->hasErrors() )
		{
			try
			{
				craft()->plugins->call( $event->registrar, array (
						$event->event,
						function ($event, BaseModel $entity, $success = TRUE)
						{
						} 
				) );
			}
			catch ( \Exception $e )
			{
				$event->addError( 'event', $e->getMessage() );
				return $event;
			}
			
			$eventRecord->save( false );
		}
		
		return $eventRecord;
	}
	
	/**
	 * Process the 'save emailBlastType' action.
	 *
	 * @param SproutEmail_EmailBlastTypeModel $emailBlastType            
	 * @throws \Exception
	 * @return int EmailBlastTypeRecordId
	 */
	public function saveEmailBlastType(SproutEmail_EmailBlastTypeModel $emailBlastType, $tab = 'info')
	{

		if ($emailBlastType->id) 
		{
			$emailBlastTypeRecord = SproutEmail_EmailBlastTypeRecord::model()->findById( $emailBlastType->id );
			$oldEmailBlastType = SproutEmail_EmailBlastTypeModel::populateModel($emailBlastTypeRecord);
		}
		

		// since we have to perform saves on multiple entities,
		// it's all or nothing using sql transactions
		$transaction = craft()->db->beginTransaction();
        
		switch ($tab)
		{
			case 'template' :
				try
				{
					$emailBlastTypeRecord = $this->_saveEmailBlastTypeTemplates( $emailBlastType );
					if ( $emailBlastTypeRecord->hasErrors() ) // no good
					{
						$transaction->rollBack();
						return false;
					}
				}
				catch ( \Exception $e )
				{
					throw new Exception( Craft::t( 'Error: EmailBlastType could not be saved.' ) );
				}
				break;
			case 'recipients' : // save & associate the recipient list
				$emailBlastTypeRecord = SproutEmail_EmailBlastTypeRecord::model()->findById( $emailBlastType->id );
				$emailBlastType->emailProvider = $emailBlastTypeRecord->emailProvider;
				$service = 'sproutEmail_' . lcfirst( $emailBlastTypeRecord->emailProvider );
				if ( ! craft()->{$service}->saveRecipientList( $emailBlastType, $emailBlastTypeRecord ) )
				{
					$transaction->rollback();
					return false;
				}
				break;
			default : // save the emailBlastType
				
				try
				{
					// Save Field Layout
					
					// Delete our previous record
					craft()->fields->deleteLayoutById($oldEmailBlastType->fieldLayoutId);

					$fieldLayout = $emailBlastType->getFieldLayout();

					// Save the field layout
					craft()->fields->saveLayout($fieldLayout);

					// Assign our new layout id info to our 
					// form model and records
					$emailBlastType->fieldLayoutId = $fieldLayout->id;
					$emailBlastType->setFieldLayout($fieldLayout);
					$emailBlastTypeRecord->fieldLayoutId = $fieldLayout->id;

					// Save the Email Blast Type
					$emailBlastTypeRecord = $this->_saveEmailBlastTypeInfo( $emailBlastType );

					if ( $emailBlastTypeRecord->hasErrors() ) // no good
					{
						$transaction->rollBack();
						return false;
					}
				}
				catch ( \Exception $e )
				{	
					throw new Exception( Craft::t( 'Error: EmailBlastType could not be saved.' ) );
				}
				break;
		}
		
		$transaction->commit();
		
		return $emailBlastTypeRecord->id;
	}
	private function _saveEmailBlastTypeInfo(SproutEmail_EmailBlastTypeModel &$emailBlastType)
	{
		$oldEmailBlastTypeEmailProvider = null;
		
		if ( isset( $emailBlastType->id ) && $emailBlastType->id ) // this will be an edit
		{
			$emailBlastTypeRecord = SproutEmail_EmailBlastTypeRecord::model()->findById( $emailBlastType->id );
			
			if ( ! $emailBlastTypeRecord )
			{
				throw new Exception( Craft::t( 'No emailBlastType exists with the ID “{id}”', array (
						'id' => $emailBlastType->id 
				) ) );
			}
			
			$oldEmailBlastTypeEmailProvider = $emailBlastTypeRecord->emailProvider;
		}
		else
		{
			$emailBlastTypeRecord = new SproutEmail_EmailBlastTypeRecord();
		}
		
		// Set common attributes
		$emailBlastTypeRecord->fieldLayoutId = $emailBlastType->fieldLayoutId;
		$emailBlastTypeRecord->name = $emailBlastType->name;
		$emailBlastTypeRecord->subject = $emailBlastType->subject;
		$emailBlastTypeRecord->fromEmail = $emailBlastType->fromEmail;
		$emailBlastTypeRecord->fromName = $emailBlastType->fromName;
		$emailBlastTypeRecord->replyToEmail = $emailBlastType->replyToEmail;
		$emailBlastTypeRecord->emailProvider = $emailBlastType->emailProvider;
		$emailBlastTypeRecord->templateOption = $emailBlastType->templateOption;
		
		// if this is a notification and replyToEmail does NOT contain a twig variable
		// OR this is not a notification, set email rule
		if ( ($emailBlastTypeRecord->notificationEvent && ! preg_match( '/{{(.*?)}}/', $emailBlastTypeRecord->replyToEmail )) || ! $emailBlastTypeRecord->notificationEvent )
		{
			$emailBlastTypeRecord->addRules( array (
					'replyToEmail',
					'email' 
			) );
		}
		
		$emailBlastTypeRecord->validate();
		$emailBlastType->addErrors( $emailBlastTypeRecord->getErrors() );
		
		if ( ! $emailBlastTypeRecord->hasErrors() )
		{
			$emailBlastTypeRecord->save( false );
			
			// if emailProvider has changed, let's get rid of the old recipient list since it's no longer valid
			if ( $emailBlastTypeRecord->emailProvider != $oldEmailBlastTypeEmailProvider )
			{
				if ( $recipientLists = $this->getEmailBlastTypeRecipientLists( $emailBlastTypeRecord->id ) )
				{
					foreach ( $recipientLists as $list )
					{
						$this->deleteEmailBlastTypeRecipientList( $list->id, $emailBlastTypeRecord->id );
					}
				}
			}
		}
		
		return $emailBlastTypeRecord;
	}
	
	/**
	 * Save emailBlastType templates
	 *
	 * @param SproutEmail_EmailBlastTypeModel $emailBlastType            
	 * @throws Exception
	 * @return SproutEmail_EmailBlastTypeRecord
	 */
	private function _saveEmailBlastTypeTemplates(SproutEmail_EmailBlastTypeModel &$emailBlastType)
	{
		$emailBlastTypeRecord = SproutEmail_EmailBlastTypeRecord::model()->findById( $emailBlastType->id );
		
		if ( ! $emailBlastTypeRecord )
		{
			throw new Exception( Craft::t( 'No emailBlastType exists with the ID “{id}”', array (
					'id' => $emailBlastType->id 
			) ) );
		}
		
		$oldEmailBlastTypeName = $emailBlastTypeRecord->name;
		
		$emailBlastTypeRecord->templateOption = $emailBlastType->templateOption;
		
		// template specific attributes & validation
		
		switch ($emailBlastType->templateOption)
		{
			case 1 : // Import the HTML/Text on your own
				$emailBlastTypeRecord->htmlBody = $emailBlastType->htmlBody;
				$emailBlastTypeRecord->textBody = $emailBlastType->textBody;
				$emailBlastTypeRecord->addRules( array (
						'htmlBody,textBody',
						'required' 
				) );
				break;
			case 2 : // Send a text-based & html email
				$emailBlastTypeRecord->htmlBody = $emailBlastType->htmlBody;
				$emailBlastTypeRecord->textBody = $emailBlastType->textBody;
				$emailBlastTypeRecord->addRules( array (
						'textBody',
						'required' 
				) );
				break;
			case 3 : // Create a EmailBlastType based on an Entries Section and Template
				$emailBlastTypeRecord->sectionId = $emailBlastType->sectionId;
				$emailBlastTypeRecord->subjectHandle = $emailBlastType->subjectHandle;
				$emailBlastTypeRecord->htmlTemplate = $emailBlastType->htmlTemplate;
				$emailBlastTypeRecord->textTemplate = $emailBlastType->textTemplate;
				$emailBlastTypeRecord->htmlBodyTemplate = $emailBlastType->htmlBodyTemplate;
				$emailBlastTypeRecord->textBodyTemplate = $emailBlastType->textBodyTemplate;

				$emailBlastTypeRecord->addRules( array (
						'sectionId,htmlTemplate,textTemplate',
						'required' 
				) );
				break;
		}
		
		$emailBlastTypeRecord->validate();
		$emailBlastType->addErrors( $emailBlastTypeRecord->getErrors() );
		
		if ( ! $emailBlastTypeRecord->hasErrors() )
		{
			$emailBlastTypeRecord->save( false );
		}
		
		return $emailBlastTypeRecord;
	}
	
	/**
	 * Delete emailBlastType recipient list entry
	 *
	 * @param int $recipientListId            
	 * @param int $emailBlastTypeId            
	 * @return bool
	 */
	public function deleteEmailBlastTypeRecipientList($recipientListId, $emailBlastTypeId)
	{
		return craft()->db->createCommand()->delete( 'sproutemail_emailBlastType_recipient_lists', array (
				'recipientListId' => $recipientListId,
				'emailBlastTypeId' => $emailBlastTypeId 
		) );
	}
	
	/**
	 * Deletes a emailBlastType by its ID along with associations;
	 * also cleans up any remaining orphans
	 *
	 * @param int $emailBlastTypeId            
	 * @return bool
	 */
	public function deleteEmailBlastType($emailBlastTypeId)
	{
		// since we have to perform deletes on multiple entities,
		// it's all or nothing using sql transactions
		$transaction = craft()->db->beginTransaction();
		
		try
		{
			$emailBlastTypeRecord = SproutEmail_EmailBlastTypeRecord::model()->findByPk( $emailBlastTypeId );
			
			// delete emailBlastType
			if ( ! craft()->db->createCommand()->delete( 'sproutemail_emailblasts', array (
					'id' => $emailBlastTypeId 
			) ) )
			{
				$transaction->rollback();
				return false;
			}
			
			// delete associated recipients
			$service = 'sproutEmail_' . lcfirst( $emailBlastTypeRecord->emailProvider );
			craft()->{$service}->deleteRecipients( $emailBlastTypeRecord );
		}
		catch ( \Exception $e )
		{
			$transaction->rollback();
			return false;
		}
		
		$transaction->commit();
		return true;
	}
	
	/**
	 * Delete notification event
	 *
	 * @param int $id            
	 * @return boolean
	 */
	public function deleteEvent($id)
	{
		if ( ! craft()->db->createCommand()->delete( 'sproutemail_notification_events', array (
				'id' => $id 
		) ) )
		{
			$transaction->rollback();
			return false;
		}
		return true;
	}
	
	/**
	 * Returns all available system frontend templates
	 *
	 * @return array
	 */
	public function getTemplatesDirListing()
	{
		$templates_path = craft()->path->getSiteTemplatesPath();
		$files = $this->_scan( $templates_path );
		$select_options = array ();
		
		// set keys the same as values for <select> element
		foreach ( $files as $file )
		{
			$fileArr = explode( '.', $file );
			array_pop( $fileArr );
			$select_options [$file] = implode( '.', $fileArr );
		}
		return $select_options;
	}
	
	public function getPlainTextFields()
	{
	    $fields = array();
	    $fields[""] = "---------";
        foreach (craft()->fields->getAllFields() as $field)
        {
            // Grab the plain text fields 
            // and store them in a key:value array
                if($field->type == 'PlainText')
                {
                    $fields[$field->handle] = $field->name;
                }
        }
        // Sort them alphabetically
            ksort($fields);
        
	    return $fields;
	}
	
	/**
	 * Returns all emailBlastType notifications
	 *
	 * @return array
	 */
	public function getNotifications()
	{
		return SproutEmail_EmailBlastTypeRecord::model()->getNotifications();
	}
	
	/**
	 * Returns all supported notification events
	 *
	 * @return array
	 */
	public function getNotificationEvents($event = null)
	{
		if ( $event )
		{
			$criteria = new \CDbCriteria();
			$criteria->condition = 'event!=:event';
			$criteria->params = array (
					':event' => 'craft' 
			);
		}
		$events = SproutEmail_NotificationEventRecord::model()->findAll();
		$events_list = array ();
		foreach ( $events as $event )
		{
			$events_list [$event->id] = $event;
		}
		return $events_list;
	}
	
	/**
	 * Returns single notification event
	 *
	 * @return array
	 */
	public function getNotificationEventById($id = null)
	{
		return SproutEmail_NotificationEventRecord::model()->findByPk( $id );
	}
	
	/**
	 * Returns event option file names
	 *
	 * @return array
	 */
	public function getNotificationEventOptions()
	{
		$options = $this->_scan( dirname( __FILE__ ) . '/../templates/notifications/_options' );
		
		$criteria = new \CDbCriteria();
		$criteria->condition = 'registrar!=:registrar';
		$criteria->params = array (
				':registrar' => 'craft' 
		);
		$events = SproutEmail_NotificationEventRecord::model()->findAll( $criteria );

		foreach ( $events as $event )
		{
			$options ['plugin_options'] [$event->id] = $event->options;
		}
		
		return $options;
	}
	
	/**
	 * Get subscription users given element id
	 *
	 * @param string $elementId            
	 */
	public function getSubscriptionUsersByElementId($elementId = null)
	{
		$users = array ();
		$criteria = new \CDbCriteria();
		$criteria->condition = 'elementId=:elementId';
		$criteria->params = array (
				':elementId' => $elementId 
		);
		
		if ( $subscriptions = SproutEmail_SubscriptionRecord::model()->findAll( $criteria ) )
		{
			$criteria = craft()->elements->getCriteria( 'User' );
			
			foreach ( $subscriptions as $subscription )
			{
				$criteria->id = $subscription->elementId;
				$users [] = craft()->elements->findElements( $criteria );
			}
		}
		
		return $users;
	}
	
	/**
	 * Recursive directory scan
	 *
	 * @param string $dir            
	 * @param sring $prefix            
	 * @return array
	 */
	private function _scan($dir, $prefix = '')
	{
		$dir = rtrim( $dir, '\\/' );
		$result = array ();
		
		foreach ( scandir( $dir ) as $f )
		{
			if ( $f !== '.' and $f !== '..' )
			{
				if ( is_dir( "{$dir}/{$f}" ) )
				{
					$result = array_merge( $result, $this->_scan( "{$dir}/{$f}", "{$prefix}{$f}/" ) );
				}
				else
				{
					$result [] = $prefix . $f;
				}
			}
		}
		return $result;
	}
}
