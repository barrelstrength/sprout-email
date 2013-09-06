<?php
namespace Craft;

class MasterBlasterVariable
{
    /**
     * Plugin Name
     * Make your plugin name available as a variable
     * in your templates as {{ craft.YourPlugin.name }}
     *
     * @return string
     */
    public function getName()
    {
        $plugin = craft()->plugins->getPlugin('masterblaster');

        return $plugin->getName();
    }

    public function createTriggerArray($formData = null)
    {
        $craftTriggers = array(
            'userSave' => 'When a user profile is saved'
        );

        $thirdPartyTriggers = craft()->plugins->callHook('masterBlasterTrigger', array($formData));

        $thirdPartyTriggerArray = array();
        foreach ($thirdPartyTriggers as $key => $triggers) {
            foreach ($triggers['hooks'] as $_key => $hook) {
                $thirdPartyTriggerArray[$hook['hook']] = $hook['name'];
            }
        }

        return array_merge($craftTriggers, $thirdPartyTriggerArray);
    }

    /**
     * Get All Campaigns
     * By default, this returns all campaigns that are normal campaigns.  If you want
     * to get only Section-based campaigns, pass 'true' to the $sectionId paramenter
     * @todo  - make this more intuitive, kinda clunky parameter names
     * 
     * @return mixed Campaign model
     */
    public function getCampaigns()
    {
    	return craft()->masterBlaster->getAllCampaigns();
    }
    
    /**
     * Get All Section Based Campaigns
     * By default, this returns all campaigns that are normal campaigns.  If you want
     * to get only Section-based campaigns, pass 'true' to the $sectionId paramenter
     * @todo  - make this more intuitive, kinda clunky parameter names
     *
     * @return mixed Campaign model
     */
    public function getSectionCampaigns()
    {
    	//Craft::dump(craft()->getBaseUrl(true));die();
    	return craft()->masterBlaster->getSectionCampaigns();
    }

    /**
     * Get a Campaign by id
     *
     * @param  int    $campaignId
     * @return object campaign record
     */
    public function getCampaignById($campaignId)
    {
        return craft()->masterBlaster->getCampaign(array('id' => $campaignId));
    }

    /**
     * Get All Sections for Options dropdown
     * 
     * @param  [type] $indexBy [description]
     * @return [type]          [description]
     */
    public function getAllSections($indexBy = null)
    {
        $result = craft()->sections->getAllSections($indexBy);

        $options = array(array(
            'label' => 'Select a Section...',
            'value' => ''
        ));

        foreach ($result as $key => $section) {

            array_push($options, array(
                'label' => $section->name,
                'value' => $section->id
            ));
        }
        
        return $options;
    }
    
    public function getSubscriberList($provider = 'masterblaster')
    {
    	$service = 'masterBlaster_' . $provider;
    	return craft()->{$service}->getSubscriberList();
    }


    public function getTemplatesDirListing()
    {
		return craft()->masterBlaster->getTemplatesDirListing();
    }
    
    public function getEmailProviders()
    {
		return craft()->masterBlaster_emailProvider->getEmailProviders();
    }

    public function getNotifications()
    {
    	return craft()->masterBlaster->getNotifications();
    }
    
    public function getNotificationEvents()
    {
    	return craft()->masterBlaster->getNotificationEvents();
    }

    public function dump($mixed)
    {
    	Craft::dump($mixed);die();
    }
    
    public function test()
    {
    	craft()->masterBlaster->deleteCampaign(10);
    }
}
