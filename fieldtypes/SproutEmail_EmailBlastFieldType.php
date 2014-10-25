<?php
namespace Craft;

class SproutEmail_EmailBlastFieldType extends BaseFieldType
{
	/**
	 * Field Type name
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t( 'SproutEmail Email Blast' );
	}
	
	/**
	 * Define database column
	 *
	 * @return false
	 */
	public function defineContentAttribute()
	{
		return AttributeType::String;
	}


    /**
     * getInputHtml function.
     * 
     * @access public
     * @param mixed $name
     * @param mixed $value
     * @return void
     */
    public function getInputHtml($name, $value)
    {
        // Set up an array of the defaults
            $emailBlastTypeRecord = new SproutEmail_EmailBlastTypeModel();
            $emailBlastType = SproutEmail_EmailBlastTypeModel::populateModel( $emailBlastTypeRecord );

            $fields = array(
                                "emailBlastType"                      => $emailBlastType,
                                "emailProviderRecipientListId"  => "",
                                "enabled"                       => FALSE,
                                "sectionId"                     => $this->element->sectionId
                            );

        // Get the section emailBlastType settings
            $sectionEmailBlastType = SproutEmail_EmailBlastTypeRecord::model()->getEmailBlastTypeBySectionId($fields["sectionId"]);

            if(!$sectionEmailBlastType){
                // The section isn't email enabled
                    return $fields;
            }
            else
            {
                // Its enabled 
                    $fields["enabled"] = TRUE;
                                    
                    // Get the RecipientListId
                        $list = craft()->sproutEmail->getEmailBlastTypeRecipientLists($sectionEmailBlastType["id"] );
                        
                        $fields["emailBlastType"]["emailProviderRecipientListId"] = array();
                        if(isset($list))
                        {
                            $arr = array();
                            foreach($list as $recipients)
                            {
                                $arr[] = $recipients->emailProviderRecipientListId;
                            }
                            $fields["emailBlastType"]["emailProviderRecipientListId"] = $arr;
                        }
    
                    // Merge on the 
                        $keys = array('fromName','fromEmail','replyToEmail','emailProvider');
                        foreach($keys as $val)
                        {
                            $fields["emailBlastType"][$val] = $sectionEmailBlastType[$val];    
                        }
            }
        
        // Merge on the entry level settings 
            $value = json_decode($value,TRUE);
            if(!empty($value))
            {
                foreach($value as $key=>$val)
                {
                    if($key == 'emailProviderRecipientListId'){
                        if(is_string($val))
                        {
                            $val = (array) $val;
                        }
                    }
                    $fields["emailBlastType"][$key] = $val;
                }
            }

        return craft()->templates->render('/sproutemail/_cp/fieldtypes/emailblast/input', array(
            'name'      => $name,
            'value'     => $fields
        ));
    }

    public function prepValue($value)
    {
        return $value;
    }

    /**
     * prepValueFromPost function.
     * 
     * @access public
     * @param mixed $value
     * @return void
     */
    public function prepValueFromPost($value)
    {
        $fields = array('fromName','fromEmail','replyToEmail','emailProviderRecipientListId');
        $newValue = array();
        foreach($fields as $f){
            $post = craft()->request->getPost('fields.'.$f);
            if(is_array($post)){
                foreach($post as $key => $val)
                {
                    $i = 0;
                    if(is_array($val)){
                        foreach($val as $final)
                        {
                            $newValue[$f][$i] = $final;
                            $i++;
                        }
                    }else{
                        $newValue[$f][$key] = $val;
                        $i++;
                    }
                }
            }else{
                $newValue[$f] = $post;
            }
        }
        $value = json_encode($newValue);
        return $value;
    }

    protected function defineSettings()
    {
        return array(
            'initialSlots' => array(AttributeType::Number, 'min' => 0)
        );
    }
    
    public function getSettingsHtml()
    {
        return craft()->templates->render('/sproutemail/_cp/fields/emailblast/settings', array(
            'settings' => $this->getSettings()
        ));
    }
    
}
