<?php
namespace Craft;

class SproutEmail_EntryRecord extends BaseRecord
{
	/**
	 * Return table name
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'sproutemail_entries';
	}
	
	/**
	 * Define attributes
	 *
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'subjectLine' => AttributeType::String,
			'sent'        => AttributeType::Bool,
		);
	}
	
	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'element' => array(static::BELONGS_TO, 'ElementRecord', 'id', 'required' => true, 'onDelete' => static::CASCADE),
			'campaign' => array(static::BELONGS_TO, 'SproutEmail_CampaignRecord', 'required' => true, 'onDelete' => static::CASCADE),
		);
	}
}
