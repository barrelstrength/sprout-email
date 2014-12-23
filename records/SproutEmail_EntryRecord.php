<?php
namespace Craft;

/**
 * Class SproutEmail_EntryRecord
 *
 * @package Craft
 * --
 * @property int    $id
 * @property int    $campaignId
 * @property int    $recipientListId
 * @property string $subjectLine
 * @property string $fromName
 * @property string $fromEmail
 * @property string $replyTo
 * @property bool   $sent
 */
class SproutEmail_EntryRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'sproutemail_campaigns_entries';
	}

	/**
	 * @todo Device a way to store sender info as a relationship if it makes more sense
	 *
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'subjectLine' => array(AttributeType::String, 'required' => true),
			'campaignId'  => array(AttributeType::Number, 'required' => true),
			'fromName'    => array(AttributeType::String, 'required' => true, 'minLength' => 2, 'maxLength' => 100),
			'fromEmail'   => array(AttributeType::Email, 'required' => true),
			'replyTo'     => array(AttributeType::Email, 'required' => false),
			'sent'        => AttributeType::Bool,
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'element'       => array(
				static::BELONGS_TO,
				'ElementRecord',
				'id',
				'required' => true,
				'onDelete' => static::CASCADE
			),
			'campaign'      => array(
				static::BELONGS_TO,
				'SproutEmail_CampaignRecord',
				'required' => true,
				'onDelete' => static::CASCADE
			),
			'recipientList' => array(
				static::HAS_ONE,
				'SproutEmail_EntryRecipientListRecord',
				'entryId'
			)
		);
	}
}
