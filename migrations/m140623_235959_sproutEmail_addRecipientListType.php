<?php

namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m140623_235959_sproutEmail_addRecipientListType extends BaseMigration
{
    /**
     * Any migration code in here is wrapped inside of a transaction.
     *
     * @return bool
     */
    public function safeUp()
    {
        $tableName = 'sproutemail_recipient_lists';
        $table = $this->dbConnection->schema->getTable( '{{' . $tableName . '}}' );
        
        if ( $table )
        {
            if ( ($column = $table->getColumn( 'type' )) == null )
            {
                Craft::log( 'Adding `type` column to the `'. $tableName . '` table.', LogLevel::Info, true );
                
                $this->addColumnAfter( $tableName, 'type', array (
                        AttributeType::String,
                        'required' => false 
                ), 'id' );
                
                Craft::log( 'Added `type` column to the `'. $tableName . '` table.', LogLevel::Info, true );
            }
            else
            {
                Craft::log( 'Tried to add a `type` column to the `'. $tableName . '` table, but there is already one there.', LogLevel::Warning );
            }
        }
        else
        {
            Craft::log( 'Could not find an ' . $tableName . ' table. Wut?', LogLevel::Error );
        }
        
        return true;
    }
}