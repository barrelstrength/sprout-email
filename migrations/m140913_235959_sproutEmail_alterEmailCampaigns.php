<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m140913_235959_sproutEmail_alterEmailCampaigns extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{		
	    $tableName = 'sproutemail_campaigns';
		$table = $this->dbConnection->schema->getTable('{{' . $tableName . '}}');
        
        $fields = array('htmlBodyTemplate','textBodyTemplate');
        
        if ( $table )
        {
            foreach($fields as $f)
            {
                if ( ($column = $table->getColumn( $f )) == null )
                {
                    Craft::log( 'Adding `'.$f.'` column to the `'. $tableName . '` table.', LogLevel::Info, true );
                    
                    $this->addColumnAfter( $tableName, $f, 'string','htmlTemplate');

                    Craft::log( 'Added `'.$f.'` column to the `'. $tableName . '` table.', LogLevel::Info, true );
                }
                else
                {
                    Craft::log( 'Tried to add a `type` column to the `'. $tableName . '` table, but there is already one there.', LogLevel::Warning );
                }
            }
        }
        else
        {
            Craft::log( 'Could not find an ' . $tableName . ' table. Wut?', LogLevel::Error );
        }

		return true;
	}
}
