<?php
namespace Craft;

/**
 * Recipient list model
 */
class SproutEmail_RecipientListModel extends BaseModel
{
	
	/**
	 * Recipient List Model
	 * 
	 * @return array
	 */
	public function defineAttributes()
	{
		return array (
			'emailProviderRecipientListId' => AttributeType::String,
			'emailProvider'                => AttributeType::String,
			'type'                         => AttributeType::String
		);
	}
	
	/**
	 * Record relationships
	 *
	 * @return array
	 */
	// public function defineRelations()
	// {
	// 	return array (
	// 		'campaignRecipientList' => array (
	// 			self::HAS_MANY,
	// 			'SproutEmail_CampaignRecipientListRecord',
	// 			'recipientListId' 
	// 		),
	// 		'campaign' => array (
	// 			self::HAS_MANY,
	// 			'SproutEmail_CampaignRecord',
	// 			'campaignId',
	// 			'through' => 'campaignRecipientList' 
	// 		) 
	// 	);
	// }
}
