<?php
namespace Craft;

class SproutEmail_SubscriptionRecord extends BaseRecord
{
	/**
	 * Return table name corresponding to this record
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'sproutemail_subscriptions';
	}
	
	/**
	 * These have to be explicitly defined in order for the plugin to install
	 *
	 * @return array
	 */
	public function defineAttributes()
	{
		return array ();
	}
	
	/**
	 * Relationships
	 *
	 * @return multitype:multitype:string boolean
	 */
	public function defineRelations()
	{
		return array (
			'user' => array (
				static::BELONGS_TO,
				'UserRecord',
				'required' => true,
				'onDelete' => static::CASCADE 
			),
			'element' => array (
				static::BELONGS_TO,
				'ElementRecord',
				'required' => true,
				'onDelete' => static::CASCADE 
			) 
		);
	}
}
