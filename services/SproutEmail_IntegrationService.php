<?php
namespace Craft;

/**
 * Service for plugin integration
 *
 */
class SproutEmail_IntegrationService extends BaseApplicationComponent
{
	private $plugin_events;
	private $existing_events;
	
	/**
	 * Register plugin events with SproutEmail
	 * @return void
	 */
	public function registerEvents()
	{
		// get plugin events
		$this->_setAllPluginEvents();
		
		// set existing events to compare to
		$this->_setExistingEvents();
		
		foreach($this->plugin_events as $events)
		{
			foreach($events as $event)
			{
				$this->_registerEvent($event);
			}
		}
		
		$this->_cleanUpEvents();
	}
	
	/**
	 * Delete stale events
	 * @return void
	 */
	private function _cleanUpEvents()
	{
		// compile list of events from parsed classes for easy comparison
		$plugin_events = array();
		foreach($this->plugin_events as $events)
		{
			foreach($events as $event)
			{
				$plugin_events[$event->getHook()][$event->getEvent()] = true;
			}			
		}

		// go through each existing event and check if there is a corresponding fresh event; if not, delete
		foreach($this->existing_events as $event)
		{
			if( ! isset($plugin_events[$event->registrar][$event->event]) 
					|| ! $plugin_events[$event->registrar][$event->event])
			{
				// delete if the plugin doesn't exist anymore
				craft()->db->createCommand()->delete('sproutemail_notification_events', array('registrar' => $event->registrar));
				
				// disassociate
				craft()->db->createCommand()->delete('sproutemail_campaign_notification_events', array('notificationEventId' => $event->id));
			}
		}
	}

	/**
	 * Register plugin events
	 * @return void
	 */
	private function _setAllPluginEvents()
	{
		if( ! $plugins = craft()->plugins->getPlugins())
		{
			return false;
		}
		 
		$events = array();
		foreach($plugins as $plugin)
		{
			if( $event = $this->_getPluginEvents($plugin))
			{
				$events[$plugin->getName()] = $event;		
			}	
		}

		$this->plugin_events = $events;
	}
	
	/**
	 * Set existing events 
	 * @return void
	 */
	private function _setExistingEvents()
	{
		// get registered plugin events
		$criteria = new \CDbCriteria();
		$criteria->condition = 'registrar!=:registrar';
		$criteria->params = array(':registrar' => 'craft');
		$this->existing_events = SproutEmail_NotificationEventRecord::model()->findAll($criteria);
	}
	
	/**
	 * Register event
	 * @param object $event
	 * @return void
	 */
	private function _registerEvent($event)
	{
		// check if event already exists
		$event_event = $event->getEvent();
		$event_hook = $event->getHook();
		$existing_event = false;
		foreach($this->existing_events as $existing)
		{
			if($existing->event == $event_event && $existing->registrar == $event_hook)
			{
				$existing_event = $existing->id;
				break;
			}
		}		

		if($existing_event) // if exists, update
		{
			$new_event = SproutEmail_NotificationEventRecord::model()->findByPk($existing_event);

		}	
		else // if doesn't exist, create
		{
			$new_event = new SproutEmail_NotificationEventRecord();	
			$new_event->registrar = $event_hook;
			$new_event->event = $event_event;
		}		

		$new_event->description = $event->getName();
		$options['html'] = $event->getOptionsHtml();
		$options['handler'] = get_class($event) . '::processOptions';
		$options['handler_location'] = $event->file_location;
		$options['handler_base_classes'] = $event->base_classes;
		$new_event->options = json_encode($options);
		$new_event->save(false);
	}
	
	/**
	 * Returns integration objects
	 * @param string $plugin
	 * @return array|objects 
	 */
	private function _getPluginEvents($plugin)
	{
		if( ! $dir = $this->_getPluginDirectory($plugin))
		{
			return false;
		}
	
		$plugin_name = str_replace(' ', '', $plugin->getName());
	
		if( ! is_dir($dir . 'integrations/sproutemail'))
		{
			return false;
		}
		
		$plugin_files = array();
		$base_class_files = array();
		foreach (scandir($dir . 'integrations/sproutemail') as $file)
		{
			if ($file !== '.' and $file !== '..')
			{
				$parts = explode('.', $file);
				$class = 'Craft\\' . $parts[0];
				
				if(strpos($class, '_Base') !== false)
				{
					$base_class_files[$class] = $dir . 'integrations/sproutemail/' . $file;
				}
				else 
				{
					$plugin_files[$class] = $dir . 'integrations/sproutemail/' . $file;
				}
			}
		}
		
		// first we need to load base classes which will be extended but not instantiated directly
		foreach($base_class_files as $file)
		{
			require_once($file);
		}
		
		// now we can instantiate the plugin event files
		$plugin_objects = array();
		foreach($plugin_files as $class => $file)
		{
			require_once($file);		
			$obj = new $class();
			$obj->file_location = $file;
			$obj->base_classes = json_encode($base_class_files);
			$plugin_objects[] = $obj;
		}
		
		return $plugin_objects;
	}
	
	/**
	 * Return plugin directory path
	 * @param string $plugin
	 * @return boolean|string directory path
	 */
	private function _getPluginDirectory($plugin)
	{
		$name = end(explode('\\', get_class($plugin)));
		$dir = str_replace('plugin', '', strtolower($name));
		if( ! file_exists(dirname(__FILE__) . '/../../' . $dir ))
		{
			return false;
		}
		return dirname(__FILE__) . '/../../' . $dir . '/';
	}
}
