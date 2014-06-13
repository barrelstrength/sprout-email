<?php

namespace Craft;

class SproutEmail_RecipientRecord extends BaseRecord
{
    /**
     * Return table name corresponding to this record
     *
     * @return string
     */
    public function getTableName()
    {
        return 'sproutemail_recipients';
    }
    
    /**
     * These have to be explicitly defined in order for the plugin to install
     *
     * @return array
     */
    public function defineAttributes()
    {
        return array (
                'name' => array (
                        AttributeType::String 
                ),
                'email' => array (
                        AttributeType::String 
                ),
                'dateCreated' => array (
                        AttributeType::DateTime 
                ),
                'dateUpdated' => array (
                        AttributeType::DateTime 
                ) 
        );
    }
    
    /**
     * Yii style validation rules;
     *
     * @return array
     */
    public function rules()
    {
        return array (
                array (
                        'email',
                        'required' 
                ), // required fields
                array (
                        'email',
                        'email' 
                )  // must be valid emails
                );
    }
}
