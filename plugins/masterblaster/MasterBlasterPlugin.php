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
    					'event' => 'content.saveContent',
    					'description' => 'when content is saved'
    			),
    			array(
    					'event' => 'users.beforeSaveProfile',
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
        craft()->on('entries.saveEntry', array($this, 'onSaveEntry'));
        craft()->on('users.saveUser', array($this, 'onSaveUser'));
        craft()->on('users.saveProfile', array($this, 'onSaveProfile'));
        craft()->on('globals.saveGlobalContent', array($this, 'onSaveGlobalContent'));
        craft()->on('assets.saveFileContent', array($this, 'onSaveFileContent'));
        craft()->on('content.saveContent', array($this, 'onSaveContent'));
    }

    public function onSaveEntry(Event $event)
    {
		$this->_processEvent('entries.saveEntry', $event);
    }

    public function onSaveUser(Event $event)
    {
        $user = $event->params['user'];
    }

    public function onSaveProfile(Event $event)
    {
        $user = $event->params['user'];
    }

    public function onSaveGlobalContent(Event $event)
    {
        $globalSet = $event->params['globalSet'];
    }

    public function onSaveFileContent(Event $event)
    {
        $file = $event->params['file'];
    }

    public function onSaveContent(Event $event)
    {
        $content = $event->params['content'];
    }
    
    private function _processEvent($eventType, $event)
    {
    	// get registered entries
    	if($res = craft()->masterBlaster_notifications->getEventNotifications($eventType))
    	{
    		foreach($res[0]->campaign as $campaign)
    		{
    			if( ! $campaign->recipientList)
    			{
    				return false;
    			}
    			 
    			try
    			{
    				$campaign->textBody = craft()->templates->renderString($campaign->textBody, array('entry' => $event->params['entry']));
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
