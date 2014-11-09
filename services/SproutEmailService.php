<?php

namespace Craft;

/**
 * Main SproutEmail service
 */
class SproutEmailService extends BaseApplicationComponent
{
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
	
	// @TODO - filter these to only include the Text fields 
	// specific to the override Email Blast Type
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
	 * Recursive directory scan
	 * @todo - does Craft have a helper for this?  Do we even need this?
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
