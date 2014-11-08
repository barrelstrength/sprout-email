<?php
namespace Craft;

/**
 * Base email provider service
 */
class SproutEmail_EmailProviderService extends BaseApplicationComponent
{
	/**
	 * Returns supported email providers based on which libraries
	 * are installed in /sproutemail/libraries
	 *
	 * @return multitype:string NULL
	 */
	public function getEmailProviders()
	{
		$plugins_path = rtrim( craft()->path->getPluginsPath(), '\\/' );
		$files = scandir( $plugins_path . '/sproutemail/libraries' );
		$select_options = array (
			'SproutEmail' => 'SproutEmail' 
		);
		// $select_options = array();
		
		// set <select> values and text to be the same
		foreach ( $files as $file )
		{
			if ( $file !== '.' && $file !== '..' )
			{
				$select_options [$file] = ucwords( str_replace( '_', ' ', $file ) );
			}
		}
		
		return $select_options;
	}
	
	/**
	 * Base function for exporting section based emails
	 *
	 * @param int $entryId            
	 * @param int $emailBlastTypeId            
	 */
	public function exportEmailBlast($entryId, $emailBlastTypeId, $return = false)
	{
		// Define our variables
		$listIds = array ();
		$listProviders = array ();
		
		// Get our emailBlastType info
		if ( ! $emailBlastType = craft()->sproutEmail->getEmailBlastTypeByEntryAndEmailBlastTypeId( $entryId, $emailBlastTypeId ) )
		{
			SproutEmailPlugin::log("EmailBlastType not found");

			if ( $return )
			{
				return false;
			}

			// @TODO - update use of die
			die( 'EmailBlastType not found' );
		}

		// Get our recipient list info
		if($emailBlastType["emailProvider"] != 'CopyPaste')
		{
			if ( ! $recipientLists = craft()->sproutEmail->getEmailBlastTypeRecipientLists( $emailBlastType ['id'] ) )
			{
				SproutEmailPlugin::log("Recipient lists not found");

				if ( $return )
				{
					return false;
				}

				// @TODO - update use of die
				die( 'Recipient lists not found' );
			}
		}
		else
		{
			SproutEmailPlugin::log("Exporting Copy/Paste Email");

			// We can't check for a recipients list on CopyPaste since one doesn't exist
			craft()->sproutEmail_copyPaste->exportEmailBlast( $emailBlastType );

			// @TODO - update use of die
			die();
		}
		
		// Check to see if we have entry level settings and update 
		// before shuffling off to the individual service connectors. 

		$entry = craft()->entries->getEntryById($entryId);
		$entryFields = $entry->getFieldLayout()->getFields();

		// Assume we have no override, and update overrideHandle if we do
		$overrideHandle = "";

		foreach ($entryFields as $field) 
		{
			// If we have an Email EmailBlastType Field, grab the handle of the first one that matches
			if ($field->getField()->type == 'SproutEmail_EmailEmailBlastType')
			{
				SproutEmailPlugin::log('We have a EmailBlastType Override field');
				SproutEmailPlugin::log('Override Field Handle: ' . $field->getField()->handle);

				$overrideHandle = $field->getField()->handle;
				continue;
			}
		}
	
		// If the entry has an override handle assigned to it
		if ($overrideHandle != "")
		{
			// Grab our Email EmailBlastType override settings
			$entryOverrideSettings = $entry->{$overrideHandle};
			$entryOverrideSettings = json_decode($entryOverrideSettings,TRUE);
			
			SproutEmailPlugin::log('Our override settings: ' . $entry->{$overrideHandle});

			// Merge the entry level settings with our emailBlastType
			$emailProviderRecipientListId = '';

			if( isset($entryOverrideSettings) )
			{
				foreach($entryOverrideSettings as $key => $value)
				{
					// Override our emailBlastType settings
					$emailBlastType[$key] = $value;

					if($key == 'emailProviderRecipientListId')
					{
						// Make sure our $emailProviderRecipientListId is an array
						// @TODO - clarify what this value is for.  Is it only for Mailgun?
						$emailProviderRecipientListId = (array) $value;
						$emailBlastType[$key] = $emailProviderRecipientListId;
					}

					SproutEmailPlugin::log('Entry override ' . $key . ': ' . $value);
					
				}
			}
			
			// @TODO - need to revisit this behavior big time!
			if($emailProviderRecipientListId != '')
			{	
				foreach ($emailProviderRecipientListId as $key => $value) 
				{
					$recipientLists[0]->emailProviderRecipientListId = $entryOverrideSettings['emailProviderRecipientListId']['list'];
					$recipientLists[0]->emailProvider = $campaign['emailProvider'];
					$recipientLists[0]->type = null;

					// $recipientListOverrides[$key] = $recipientListOverride;
				}
			}
		}

		// Create the recipient list variables
		foreach ( $recipientLists as $list )
		{
			$listProviders [] = $list->emailProvider;
			$listIds [$list->emailProvider] [] = $list ['emailProviderRecipientListId'];
		}

		foreach ( $listProviders as $provider )
		{
			$provider_service = 'sproutEmail_' . lcfirst( $provider );
			craft()->{$provider_service}->exportEmailBlast( $emailBlastType, $listIds [$provider], $return );
		}
		
		if ( $return )
		{
			return true;
		}

		// @TODO - update use of die
		die();
	}
	
	/**
	 * Saves recipient list for emailBlastType
	 *
	 * @param SproutEmail_EmailBlastTypeModel $emailBlastType            
	 * @param SproutEmail_EmailBlastTypeRecord $emailBlastTypeRecord            
	 * @throws Exception
	 * @return void
	 */
	public function saveRecipientList(SproutEmail_EmailBlastTypeModel &$emailBlastType, SproutEmail_EmailBlastTypeRecord &$emailBlastTypeRecord)
	{
		// an email provider is required
		if ( ! isset( $emailBlastType->emailProvider ) || ! $emailBlastType->emailProvider )
		{
			$emailBlastType->addError( 'emailProvider', 'Unsupported email provider.' );
		}
		
		// at least one recipient list is required
		if ( ! isset( $emailBlastType->emailProviderRecipientListId ) || ! $emailBlastType->emailProviderRecipientListId )
		{
			$emailBlastType->addError( 'emailProviderRecipientListId', 'You must select at least one recipient list.' );
		}
		
		// if we have what we need up to this point,
		// get the recipient list(s) by emailProvider and emailProviderRecipientListId
		if ( ! $emailBlastType->hasErrors() )
		{
			$criteria = new \CDbCriteria();
			$criteria->condition = 'emailProvider=:emailProvider';
			$criteria->params = array (
				':emailProvider' => $emailBlastType->emailProvider 
			);
			
			$recipientListIds = ( array ) $emailBlastType->emailProviderRecipientListId;
			
			// process each recipient listEmailBlastTypes
			foreach ( $recipientListIds as $list_id )
			{
				$criteria->condition = 'emailProviderRecipientListId=:emailProviderRecipientListId';
				$criteria->params = array (
					':emailProviderRecipientListId' => $list_id 
				);
				$recipientListRecord = SproutEmail_RecipientListRecord::model()->find( $criteria );
				
				if ( ! $recipientListRecord ) // doesn't exist yet, so we need to create
				{
					// TODO: for now, we'll assume that the email provider's recipient list id is valid,
					// but ideally, we'd want to check with the api to make sure that it is in fact valid
					
					// save record
					$recipientListRecord = new SproutEmail_RecipientListRecord();
					$recipientListRecord->emailProviderRecipientListId = $list_id;
					$recipientListRecord->emailProvider = $emailBlastType->emailProvider;
				}
				
				// we already did our validation, so just save
				if ( $recipientListRecord->save() )
				{
					// associate with emailBlastType, if not already done so
					if ( SproutEmail_EmailBlastTypeRecipientListRecord::model()->count( 'recipientListId=:recipientListId AND emailBlastTypeId=:emailBlastTypeId', array (
						':recipientListId' => $recipientListRecord->id,
						':emailBlastTypeId' => $emailBlastTypeRecord->id 
					) ) == 0 )
					{
						$emailBlastTypeRecipientListRecord = new SproutEmail_EmailBlastTypeRecipientListRecord();
						$emailBlastTypeRecipientListRecord->recipientListId = $recipientListRecord->id;
						$emailBlastTypeRecipientListRecord->emailBlastTypeId = $emailBlastTypeRecord->id;
						$emailBlastTypeRecipientListRecord->save( false );
					}
				}
			}
			
			// now we need to disassociate recipient lists as needed
			foreach ( $emailBlastTypeRecord->recipientList as $list )
			{
				// was part of emailBlastType, but now isn't
				if ( ! in_array( $list->emailProviderRecipientListId, $recipientListIds ) )
				{
					craft()->sproutEmail->deleteEmailBlastTypeRecipientList( $list->id, $emailBlastTypeRecord->id );
				}
			}
		}
		else
		{
			return false;
		}
		
		$this->cleanUpRecipientListOrphans( $emailBlastTypeRecord );
		
		return true;
	}
	
	/**
	 * Deletes recipients for specified emailBlastType
	 *
	 * @param SproutEmail_EmailBlastTypeRecord $emailBlastTypeRecord            
	 * @return bool
	 */
	public function deleteRecipients(SproutEmail_EmailBlastTypeRecord $emailBlastTypeRecord)
	{
		// delete associated recipient lists
		if ( ! craft()->db->createCommand()->delete( 'sproutemail_emailblasttypes_recipientlists', array (
				'emailBlastTypeId' => $emailBlastTypeRecord->id 
		) ) )
		{
			return false;
		}
		
		if ( ! $this->cleanUpRecipientListOrphans( $emailBlastTypeRecord ) )
		{
			return false;
		}
		
		return true;
	}
	
	/**
	 * Delete recipient list
	 *
	 * @param int $recipientListId            
	 * @return bool
	 */
	public function deleteRecipientList($recipientListId)
	{
		return craft()->db->createCommand()->delete( 'sproutemail_recipient_lists', array (
				'id' => $recipientListId 
		) );
	}
	
	/**
	 * Cleans up the recipient list after saving
	 *
	 * @param SproutEmail_EmailBlastTypeRecord $emailBlastTypeRecord            
	 * @return boolean
	 */
	public function cleanUpRecipientListOrphans(&$emailBlastTypeRecord)
	{
		// clean up recipient lists if orphaned
		if ( ! empty( $emailBlastTypeRecord->recipientList ) )
		{
			// first let's prep our data
			$recipientListIds = array ();
			foreach ( $emailBlastTypeRecord->recipientList as $list )
			{
				$recipientListIds [] = $list->id;
			}
			
			// now check if there are any orphans
			$criteria = new \CDbCriteria();
			$criteria->addInCondition( 't.id', $recipientListIds );
			$criteria->condition = 'emailBlastType.id is null';
			
			$orphans = SproutEmail_RecipientListRecord::model()->with( 'emailBlastType' )->findAll( $criteria );
			
			if ( $orphans )
			{
				foreach ( $orphans as $recipientList )
				{
					if ( ! $this->deleteRecipientList( $recipientList->id ) )
					{
						return false;
					}
				}
			}
		}
		return true;
	}
}
