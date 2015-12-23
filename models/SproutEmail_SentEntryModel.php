<?php
namespace Craft;

class SproutEmail_SentEntryModel extends BaseModel
{
	protected $fields;

	public $saveAsNew;
	/**
	 * These have to be explicitly defined in order for the plugin to install
	 *
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'campaignEntryId'       => array(AttributeType::Number, 'required' => true),
			'campaignNotifcationId' => array(AttributeType::Number, 'required' => false),
			'campaign'				=> AttributeType::Mixed,
			'notification'          => AttributeType::Mixed
		);
	}
}
