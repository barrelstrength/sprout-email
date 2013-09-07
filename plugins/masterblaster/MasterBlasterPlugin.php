<?php
namespace Craft;

class MasterBlasterPlugin extends BasePlugin
{
    public function getName() 
    {
        $pluginName = Craft::t('Master Blaster');

        // The plugin name override
        $plugin = craft()->db->createCommand()
            ->select('settings')
            ->from('plugins')
            ->where('class=:class', array(':class'=> 'MasterBlaster'))
            ->queryScalar();

        $plugin = json_decode( $plugin, true );
        $pluginNameOverride = $plugin['pluginNameOverride'];

        return ($pluginNameOverride) ? $pluginNameOverride : $pluginName;
    }

    public function getVersion()
    {
        return '1.0';
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
        return craft()->templates->render('masterblaster/_settings/settings', array(
            'settings' => $this->getSettings()
        ));
    }

    /**
     * Register control panel routes
     */
    public function registerCpRoutes()
    {
        return array(
            'masterblaster\/campaigns\/new' =>
            'masterblaster/campaigns/_edit',

            'masterblaster\/campaigns\/edit\/(?P<campaignId>\d+)' =>
            'masterblaster/campaigns/_edit',
        		
        	'masterblaster\/notifications\/new' =>
        	'masterblaster/notifications/_edit',
        		
        	'masterblaster\/notifications\/edit\/(?P<campaignId>\d+)' =>
        	'masterblaster/notifications/_edit',
        );
    }

    /**
     * Register twig extension
     */
    public function addTwigExtension()
    {
        Craft::import('plugins.masterblaster.twigextensions.MasterBlasterTwigExtension');

        return new MasterBlasterTwigExtension();
    }
    
    /**
     * Add default ingredients after plugin is installed
     */
    public function onAfterInstall()
    {
    	$events = array(
    			array(
    					'event' => 'entries.saveEntry.new',
    					'description' => 'when a new entry is created'
    			),    			
    			array(
    					'event' => 'entries.saveEntry',
    					'description' => 'when an existing entry is updated'
    			),
    			array(
    					'event' => 'users.saveUser',
    					'description' => 'when user is saved'
    			),
				array(
    					'event' => 'users.saveProfile',
    					'description' => 'when user profile is saved'
    			),

    	);
    
    	foreach ($events as $event) 
    	{
    		craft()->db->createCommand()->insert('masterblaster_notification_events', $event);
    	}
    }

    /**
     * Perform some action after plugin validates and saves
     * data to the database.
     *
     * @return [type] [description]
     */
    public function senorFormAfterSaveAction($formData)
    {
        $service = $formData['subscribeData']['service'];

        $response = "";

        switch ($service) {
            case 'CampaignMonitor':

                $response = "someday";
                break;

            case 'MailChimp':

                $response = "someday";
                break;

            case 'MasterBlaster':

                $response = "someday";
                break;

            case 'CraftUsers':
                $response = "someday";
                // die('Check Craft subscribe setup and run Craft Subscribe Script!');
                break;

            default:
                $response = "sosameday";
                // die('No service selected, return blank or throw error');

                break;
        }

        return $response;

    }
    
    public function init()
    {
    	// events fired by $this->raiseEvent 
        craft()->on('entries.saveEntry', array($this, 'onSaveEntry'));
        craft()->on('users.saveUser', array($this, 'onSaveUser'));
        craft()->on('users.saveProfile', array($this, 'onSaveProfile'));
        craft()->on('globals.saveGlobalContent', array($this, 'onSaveGlobalContent'));
        craft()->on('assets.saveFileContent', array($this, 'onSaveFileContent'));
        craft()->on('content.saveContent', array($this, 'onSaveContent'));
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
    
    private function _processEvent($eventType, $entry)
    {
    	// get registered entries
    	if($res = craft()->masterBlaster_notifications->getEventNotifications($eventType, $entry))
    	{
    		foreach($res as $campaign)
    		{
    			if( ! $campaign->recipientList)
    			{
    				return false;
    			}
    			 
    			try
    			{
    				$campaign->textBody = craft()->templates->renderString($campaign->textBody, array('entry' => $entry));
    			}
    			catch (\Exception $e)
    			{
    				return false; // fail silently for now; something is wrong with the tpl
    			}
    			 
    			$recipientLists = array();
    			foreach($campaign->recipientList as $list)
    			{
    				$recipientLists[] = $list->emailProviderRecipientListId;
    			}
    			$service = 'masterBlaster_' . $campaign->emailProvider;
    			craft()->{$service}->sendCampaign($campaign, $recipientLists);
    		}
    	}
    }
    
}
