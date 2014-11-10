<?php
namespace Craft;

class SproutEmail_EmailBlastTypeService extends BaseApplicationComponent
{

	public function getEmailBlastTypes($blastType = null)
	{	
		// Grab All Blast Types by default
		$query = craft()->db->createCommand()
					->select( '*' )
					->from( 'sproutemail_emailblasttypes' )
					->order( 'dateCreated desc' );

		// If we have a specific $blastType, limit the results
		if ($blastType == EmailBlastType::EmailBlast OR 
				$blastType == EmailBlastType::Notification) 
		{
			$query->where( 'type=:type', array(':type' => $blastType));
		}
		
		$results = $query->queryAll();

		$emailBlastTypes = SproutEmail_EmailBlastTypeModel::populateModels($results);
		
		return $emailBlastTypes;
	}

	/**
	 * Gets a emailBlastType
	 *
	 * @param
	 *            array possible conditions: array('id' => <id>, 'handle' => <handle>, ...)
	 *            as defined in $valid_keys
	 * @return SproutEmail_EmailBlastTypeModel null
	 */
	// public function getEmailBlastType($conditions = array())
	// {
	// 	// we can do where clauses on these keys only
	// 	$valid_keys = array (
	// 		'id',
	// 		'handle' 
	// 	);
		
	// 	$criteria = new \CDbCriteria();
		
	// 	if ( ! empty( $conditions ) )
	// 	{
	// 		$params = array ();
	// 		foreach ( $conditions as $key => $val )
	// 		{
	// 			// only accept our defined keys
	// 			if ( ! in_array( $key, $valid_keys ) )
	// 			{
	// 				continue;
	// 			}
				
	// 			$criteria->addCondition( 't.' . $key . '=:' . $key );
	// 			$params [':' . $key] = $val;
	// 		}
			
	// 		if ( ! empty( $params ) )
	// 		{
	// 			$criteria->params = $params;
	// 		}
	// 	}
		
	// 	// get emailBlastType record with recipient lists
	// 	$emailBlastTypeRecord = SproutEmail_EmailBlastTypeRecord::model()->with( 'recipientList', 'emailBlastTypeNotificationEvent' )->find( $criteria );
		
	// 	if ( $emailBlastTypeRecord )
	// 	{
	// 		// now we need to populate the model
	// 		$emailBlastTypeModel = SproutEmail_EmailBlastTypeModel::populateModel( $emailBlastTypeRecord );
			
	// 		$unserialized = array ();
	// 		foreach ( $emailBlastTypeRecord->emailBlastTypeNotificationEvent as $event )
	// 		{
	// 			$opts = $event->options;
	// 			$event->options = isset( $opts ['options'] ) ? $opts ['options'] : array ();
	// 			$unserialized [] = $event;
	// 		}
			
	// 		$emailBlastTypeModel->notificationEvents = $unserialized;
			
	// 		// now for the recipient related data
	// 		if ( count( $emailBlastTypeRecord->recipientList ) > 0 )
	// 		{
	// 			$emailProviderRecipientListIdArr = array ();
	// 			foreach ( $emailBlastTypeRecord->recipientList as $list )
	// 			{
	// 				$emailProviderRecipientListIdArr [$list->emailProviderRecipientListId] = $list->emailProviderRecipientListId;
	// 			}
				
	// 			$emailBlastTypeModel->emailProviderRecipientListId = $emailProviderRecipientListIdArr;
	// 		}
			
	// 		return $emailBlastTypeModel;
	// 	}
	// }

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
			return new SproutEmail_EmailBlastTypeModel();
		}
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
		else
		{
			$emailBlastTypeRecord = new SproutEmail_EmailBlastTypeRecord();
		}

		// since we have to perform saves on multiple entities,
		// it's all or nothing using sql transactions
		$transaction = craft()->db->beginTransaction();
	       
		switch ($tab)
		{
			// save & associate the recipient list
			case 'recipients' :
				
				$emailBlastTypeRecord = SproutEmail_EmailBlastTypeRecord::model()->findById( $emailBlastType->id );
				$emailBlastType->emailProvider = $emailBlastTypeRecord->emailProvider;
				$service = 'sproutEmail_' . lcfirst( $emailBlastTypeRecord->emailProvider );

				if ( ! craft()->{$service}->saveRecipientList( $emailBlastType, $emailBlastTypeRecord ) )
				{
					$transaction->rollback();
					return false;
				}
				
				break;

			case 'fields' : 
				
				// Save Field Layout
				$fieldLayout = $emailBlastType->getFieldLayout();
				craft()->fields->saveLayout($fieldLayout);

				// Delete our previous record
				if ($emailBlastType->id && $oldEmailBlastType->fieldLayoutId) 
				{
					craft()->fields->deleteLayoutById($oldEmailBlastType->fieldLayoutId);	
				}

				// Assign our new layout id info to our 
				// form model and records
				$emailBlastType->fieldLayoutId = $fieldLayout->id;				
				$emailBlastTypeRecord->fieldLayoutId = $fieldLayout->id;

				// Save the Email Blast Type
				$emailBlastTypeRecord = $this->_saveEmailBlastTypeInfo( $emailBlastType );

				if ( $emailBlastTypeRecord->hasErrors() ) // no good
				{
					$transaction->rollBack();
					return false;
				}

				break;

			// save the emailBlastType
			default :
				
				if ($emailBlastType->subject) 
				{
					$emailBlastType->type = EmailBlastType::Notification;
				}
				else
				{
					$emailBlastType->type = EmailBlastType::EmailBlast;
				}

				try
				{
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
		$emailBlastTypeRecord->handle = $emailBlastType->handle;
		$emailBlastTypeRecord->type = $emailBlastType->type;
		$emailBlastTypeRecord->titleFormat = $emailBlastType->titleFormat;
		$emailBlastTypeRecord->hasUrls = $emailBlastType->hasUrls;
		$emailBlastTypeRecord->hasAdvancedTitles = $emailBlastType->hasAdvancedTitles;
		$emailBlastTypeRecord->subject = $emailBlastType->subject;
		$emailBlastTypeRecord->fromEmail = $emailBlastType->fromEmail;
		$emailBlastTypeRecord->fromName = $emailBlastType->fromName;
		$emailBlastTypeRecord->replyToEmail = $emailBlastType->replyToEmail;
		$emailBlastTypeRecord->emailProvider = $emailBlastType->emailProvider;
		
		$emailBlastTypeRecord->template = $emailBlastType->template;
		$emailBlastTypeRecord->templateCopyPaste = $emailBlastType->templateCopyPaste;

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
			if ( ! craft()->db->createCommand()->delete( 'sproutemail_emailblasttypes', array (
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
	 * Delete emailBlastType recipient list entry
	 *
	 * @param int $recipientListId            
	 * @param int $emailBlastTypeId            
	 * @return bool
	 */
	public function deleteEmailBlastTypeRecipientList($recipientListId, $emailBlastTypeId)
	{
		return craft()->db->createCommand()->delete( 'sproutemail_emailblasttypes_recipientlists', array (
				'recipientListId' => $recipientListId,
				'emailBlastTypeId' => $emailBlastTypeId 
		) );
	}
}
