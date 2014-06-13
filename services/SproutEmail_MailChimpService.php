<?php

namespace Craft;

/**
 * MailChimp service
 * Abstracted MailChimp wrapper
 */
class SproutEmail_MailChimpService extends SproutEmail_EmailProviderService implements SproutEmail_EmailProviderInterfaceService
{
    protected $apiSettings;
    protected $client_key;
    protected $api_key;
    public function __construct()
    {
        $criteria = new \CDbCriteria();
        $criteria->condition = 'emailProvider=:emailProvider';
        $criteria->params = array (
                ':emailProvider' => 'MailChimp' 
        );
        
        $res = SproutEmail_EmailProviderSettingRecord::model()->find( $criteria );
        $this->apiSettings = json_decode( $res->apiSettings );
        
        $this->api_key = isset( $this->apiSettings->api_key ) ? $this->apiSettings->api_key : '';
    }
    
    /**
     * Returns subscriber lists
     *
     * @return array
     */
    public function getSubscriberList()
    {
        require_once (dirname( __FILE__ ) . '/../libraries/MailChimp/inc/MCAPI.class.php');
        $subscriber_lists = array ();
        
        $api = new \MCAPI( $this->api_key );
        $res = $api->lists();
        
        if ( $api->errorCode )
        {
            return $subscriber_lists;
        }
        
        foreach ( $res ['data'] as $list )
        {
            $subscriber_lists [$list ['id']] = $list ['name'];
        }
        
        return $subscriber_lists;
    }
    
    /**
     * Exports campaign (no send)
     *
     * @param array $campaign            
     * @param array $listIds            
     */
    public function exportCampaign($campaign = array(), $listIds = array(), $return = false)
    {
        require_once (dirname( __FILE__ ) . '/../libraries/MailChimp/inc/MCAPI.class.php');
        
        $api = new \MCAPI( $this->api_key );
        
        $type = 'regular';
        $opts = array (
                'subject' => $campaign ['title'],
                'title' => $campaign ['name'],
                'from_name' => $campaign ['fromName'],
                
                'from_email' => $campaign ['fromEmail'],
                
                // need to look into this
                'tracking' => array (
                        'opens' => true,
                        'html_clicks' => true,
                        'text_clicks' => false 
                ),
                
                'authenticate' => true,
                'list_id' => $listIds [0] 
        );
        
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13' );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
        
        $html_url = craft()->getBaseUrl( true ) . '/' . $campaign ['htmlTemplate'] . "?sectionId={$campaign['sectionId']}" . "&handle={$campaign['handle']}" . "&entryId={$campaign['entryId']}" . "&slug={$campaign['slug']}";
        curl_setopt( $ch, CURLOPT_URL, $html_url );
        $html = curl_exec( $ch );
        
        $text_url = craft()->getBaseUrl( true ) . '/' . $campaign ['textTemplate'] . "?sectionId={$campaign['sectionId']}" . "&handle={$campaign['handle']}" . "&entryId={$campaign['entryId']}" . "&slug={$campaign['slug']}";
        curl_setopt( $ch, CURLOPT_URL, $text_url );
        $text = curl_exec( $ch );
        
        $content = array (
                'html' => $html,
                'text' => $text 
        );
        
        $result = $api->campaignCreate( $type, $opts, $content );
        
        if ( ! $api->errorCode )
            echo "Created with ID " . $result;
        else
            echo 'Error: ' . $api->errorMessage;
        
        die();
    }
    public function saveSettings($settings = array())
    {
        $criteria = new \CDbCriteria();
        $criteria->condition = 'emailProvider=:emailProvider';
        $criteria->params = array (
                ':emailProvider' => 'MailChimp' 
        );
        
        $record = SproutEmail_EmailProviderSettingRecord::model()->find( $criteria );
        $record->apiSettings = json_encode( $settings );
        return $record->save();
    }
    public function getSettings()
    {
        if ( $this->api_key )
        {
            $this->apiSettings->valid = true;
        }
        else
        {
            $this->apiSettings->valid = false;
        }
        return $this->apiSettings;
    }
    /**
     * Exports campaign (with send)
     *
     * @param array $campaign            
     * @param array $listIds            
     */
    public function sendCampaign($campaign = array(), $listIds = array())
    {
        // future feature
    }
}
