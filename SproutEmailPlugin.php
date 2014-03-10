<?php
namespace Craft;

class SproutEmailPlugin extends BasePlugin
{
    private $version = '0.6.5';
    
    public function getName() 
    {
        $pluginName = Craft::t('Sprout Email');

        // The plugin name override
        $plugin = craft()->db->createCommand()
            ->select('settings')
            ->from('plugins')
            ->where('class=:class', array(':class'=> 'SproutEmail'))
            ->queryScalar();

        $plugin = json_decode( $plugin, true );
        $pluginNameOverride = $plugin['pluginNameOverride'];

        return ($pluginNameOverride) ? $pluginNameOverride : $pluginName;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function getDeveloper()
    {
        return 'Barrel Strength Design';
    }

    public function getDeveloperUrl()
    {
        return 'http://barrelstrengthdesign.com';
    }

    public function hasCpSection()
    {
        return true;
    }

    protected function defineSettings()
    {
        return array(
            'pluginNameOverride' => AttributeType::String,
        );
    }

    public function getSettingsHtml()
    {
        return craft()->templates->render('sproutemail/_settings/settings', array(
            'settings' => $this->getSettings()
        ));
    }

    /**
     * Register control panel routes
     */
    public function registerCpRoutes()
    {
        return array(
            'sproutemail\/campaigns\/new' =>
            'sproutemail/campaigns/_edit',

            'sproutemail\/campaigns\/edit\/(?P<campaignId>\d+)' =>
            'sproutemail/campaigns/_edit',
        		
        	'sproutemail\/notifications\/new' =>
        	'sproutemail/notifications/_edit',
        		
        	'sproutemail\/notifications\/edit\/(?P<campaignId>\d+)' =>
        	'sproutemail/notifications/_edit',
        		
        	'sproutemail\/events\/new' =>
        	'sproutemail/events/_edit',
        		
        	'sproutemail\/events\/edit\/(?P<eventId>\d+)' =>
        	'sproutemail/events/_edit',
        );
    }
    
    /**
     * Add default events after plugin is installed
     */
    public function onAfterInstall()
    {
    	$events = array(
    			array(
    					'registrar' => 'craft',
    					'event' => 'entries.saveEntry.new',
    					'description' => 'Craft: When a new entry is created'
    			),    			
    			array(
    					'registrar' => 'craft',
    					'event' => 'entries.saveEntry',
    					'description' => 'Craft: When an existing entry is updated'
    			),
    			array(
    					'registrar' => 'craft',
    					'event' => 'users.saveUser',
    					'description' => 'Craft: When a user is saved'
    			),
				array(
						'registrar' => 'craft',
    					'event' => 'users.saveProfile',
    					'description' => 'Craft: When a user profile is saved'
    			)
    	);
    
    	foreach ($events as $event) 
    	{
    		craft()->db->createCommand()->insert('sproutemail_notification_events', $event);
    	}
    }
    
    /**
     * Initialize
     * @return void
     */
    public function init()
    {
    	parent::init();

    	// events fired by $this->raiseEvent 
        craft()->on('entries.saveEntry', array($this, 'onSaveEntry'));
        craft()->on('users.saveUser', array($this, 'onSaveUser'));
        craft()->on('users.saveProfile', array($this, 'onSaveProfile'));
        craft()->on('globals.saveGlobalContent', array($this, 'onSaveGlobalContent'));
        craft()->on('assets.saveFileContent', array($this, 'onSaveFileContent'));
        craft()->on('content.saveContent', array($this, 'onSaveContent'));

        $criteria = new \CDbCriteria();
        $criteria->condition = 'registrar!=:registrar';
        $criteria->params = array(':registrar' => 'craft');
        if( $events = SproutEmail_NotificationEventRecord::model()->findAll($criteria))
        {
        	foreach($events as $event)
        	{
        		try {
        			craft()->plugins->call($event->registrar,array($event->event, $this->_get_closure()));
        		} catch (\Exception $e) {
        			die($e->getMessage());
        		}
        	}
        }
    }
    
    /**
     * Anonymous function for plugin integration
     * @return function
     */
    private function _get_closure()
    {
    	/**
    	 * Event handler closure
    	 * @var String [required] - event fired
    	 * @var BaseModel [required] - the entity to be used for data extraction
    	 * @var Bool [optional] - event status; if passed, the function will exit on false and process on true; defaults to true
    	 */
    	return function($event, $entity = null, $success = TRUE)
    	{
    		// if ! $success, return
    		if( ! $success)
    		{
    			return false;
    		}
    		
    		// an event can be either an Event object or a string
    		if($event instanceof Event)
    		{
    		    $event_name = $event->params['event'];
    		} 
    		else 
    		{
    		    $event_name = (string) $event;
    		}
    		
    		// check if entity is passed in as an event param
    		if( ! $entity && isset($event->params['entity']))
    		{
    		    $entity = $event->params['entity'];
    		}

    		// validate
    		$criteria = new \CDbCriteria();
    		$criteria->condition = 'event=:event';
    		$criteria->params = array(':event' => $event_name);     	 	

    		if( ! $event_notification = SproutEmail_NotificationEventRecord::model()->find($criteria))
    		{
    			return false;
    		}
    		    		    	
    		// process $entity
    		// get registered entries
    		if($res = craft()->sproutEmail_notifications->getEventNotifications($event_name, $entity))
    		{
    			foreach($res as $campaign)
    			{    				
    				if( ! $campaign->recipients)
    				{
    					return false;
    				}

    				// set $_POST vars
    			    if($post = craft()->request->getPost())
    			    {
    			        foreach($post as $key => $val)
    			        {
    			            if(is_object($entity))
    			            {
    			                $entity->{$key} = $val;
    			            }
    			            else if (is_array($entity))
    			            {
    			                $entity[$key] = $val;
    			            }
    			        }
    			    }
    				 
    				$opts = json_decode(json_encode($event_notification->options, false));
    				 
    				if($opts && $opts->handler)
    				{
    					list($class, $function) = explode('::', $opts->handler);

    					$options = $campaign->campaignNotificationEvent[0]->options;

    					$base_classes = json_decode($opts->handler_base_classes);

						if($base_classes && ! empty($base_classes))
						{
							foreach($base_classes as $base)
							{
								require_once($base);
							}
						}

						require_once($opts->handler_location);
						
    					$obj = new $class();    					
    					if( ! method_exists($obj, $function))
    					{
    						return false;
    					}
    					
    					if( ! $obj->$function($event_name, $entity, $options))
    					{
    						return true;
    					}
    				}

    				try {
    				    $campaign->subject = craft()->templates->renderString($campaign->subject, array('entry' => $entity));
    			    } catch (\Exception $e) {
    					$campaign->subject = str_replace('{{', '', $campaign->subject);
    					$campaign->subject = str_replace('}}', '', $campaign->subject);
    				}
    				
    				try {
    				    $campaign->textBody = craft()->templates->renderString($campaign->textBody, array('entry' => $entity));
    				} catch (\Exception $e) {
    				    $campaign->textBody = str_replace('{{', '', $campaign->textBody);
    				    $campaign->textBody = str_replace('}}', '', $campaign->textBody);
    				}
    				
    				try {
    				    $campaign->htmlBody = craft()->templates->renderString($campaign->htmlBody, array('entry' => $entity));
    				} catch (\Exception $e) {
    				    $campaign->htmlBody = str_replace('{{', '', $campaign->htmlBody);
    				    $campaign->htmlBody = str_replace('}}', '', $campaign->htmlBody);
    				}
    				
    				try {
    				    $campaign->replyToEmail = craft()->templates->renderString($campaign->replyToEmail, array('entry' => $entity));
    				} catch (\Exception $e) {
    				    $campaign->replyToEmail = null;
    				}
    				
    				$recipientLists = array();
    				foreach($campaign->recipientList as $list)
    				{
    					$recipientLists[] = $list->emailProviderRecipientListId;
    				}

    				$service = 'sproutEmail_' . lcfirst($campaign->emailProvider);
    				craft()->{$service}->sendCampaign($campaign, $recipientLists);
    			}
    		}
    	};
    }

    /**
     * Available variables:
     * all entries in 'craft_content' table
     * to access: entry.id, entry.body, entry.locale, etc.
     * @param Event $event
     */
    public function onSaveEntry(Event $event)
    {    	
    	switch($event->params['isNewEntry'])
    	{
    		case true:
    			$event_type = 'entries.saveEntry.new';
    			break;
    		default:
    			$event_type = 'entries.saveEntry';
    			break;
    	}
		$this->_processEvent($event_type, $event->params['entry']);
    }

    /**
     * Available variables:
     * all entries in 'craft_users' table
     * to access: entry.id, entry.firstName, etc.
     * @param Event $event
     */
    public function onSaveUser(Event $event)
    {
        $this->_processEvent('users.saveUser', $event->params['user']);
    }

    /**
     * Available variables:
     * all entries in 'craft_users' table
     * to access: entry.id, entry.firstName, etc.
     * @param Event $event
     */
    public function onSaveProfile(Event $event)
    {
    	$this->_processEvent('users.saveProfile', $event->params['user']);
    }

    // not implemented
    public function onSaveGlobalContent(Event $event)
    {
    	
        $this->_processEvent('globals.saveGlobalContent', $event->params['globalSet']);
    }

    // not implemented
    public function onSaveFileContent(Event $event)
    {
        $this->_processEvent('assets.saveFileContent', $event->params['file']);
    }

    /**
     * Available variables:
     * all entries in 'craft_content' table
     * to access: entry.id, entry.body, entry.locale, etc.
     * @param Event $event
     */
    public function onSaveContent(Event $event)
    {
    	switch($event->params['isNewContent'])
    	{
    		case true:
    			$event_type = 'content.saveContent.new';
    			break;
    		default:
    			$event_type = 'content.saveContent';
    			break;
    	}
        $this->_processEvent($event_type, $event->params['content']);
    }
    
    /**
     * Handle system event
     * @param string $eventType
     * @param obj $entry
     * @return boolean
     */
    private function _processEvent($eventType, $entry)
    {
    	// get registered entries
    	if($res = craft()->sproutEmail_notifications->getEventNotifications($eventType, $entry))
    	{   
    		foreach($res as $campaign)
    		{
    			if( ! $campaign->recipients)
    			{
    				return false;
    			}

                // @TODO - probably want to tighten up this code.  Would it 
                // be better to switch to do a string replace and only make
                // key variables available here? 
                // entry.author, entry.author.email, entry.title
    			
                try {
                    $campaign->subject = craft()->templates->renderString($campaign->subject, array('entry' => $entry));
                } catch (\Exception $e) {
    				$campaign->subject = str_replace('{{', '', $campaign->subject);
    				$campaign->subject = str_replace('}}', '', $campaign->subject);
    			}
    			
    			try {
    			    $campaign->fromName = craft()->templates->renderString($campaign->fromName, array('entry' => $entry));
    			} catch (\Exception $e) {
    			    $campaign->fromName = str_replace('{{', '', $campaign->fromName);
    			    $campaign->fromName = str_replace('}}', '', $campaign->fromName);
    			}
    			
    			try {
    			    $campaign->textBody = craft()->templates->renderString($campaign->textBody, array('entry' => $entry));
    			} catch (\Exception $e) {
    			    $campaign->textBody = str_replace('{{', '', $campaign->textBody);
    			    $campaign->textBody = str_replace('}}', '', $campaign->textBody);
    			}
    			
    			try {
    			    $campaign->htmlBody = craft()->templates->renderString($campaign->htmlBody, array('entry' => $entry));
    			} catch (\Exception $e) {
    			    $campaign->htmlBody = str_replace('{{', '', $campaign->htmlBody);
    			    $campaign->htmlBody = str_replace('}}', '', $campaign->htmlBody);
    			}
    			
    			try {
    			    $campaign->replyToEmail = craft()->templates->renderString($campaign->replyToEmail, array('entry' => $entry));
    			} catch (\Exception $e) {
    			    $campaign->replyToEmail = null;
    			}

    			$service = 'sproutEmail_' . lcfirst($campaign->emailProvider);
    			craft()->{$service}->sendCampaign($campaign);
    		}
    	}
    }
    
}
