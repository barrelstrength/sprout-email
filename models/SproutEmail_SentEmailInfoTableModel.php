<?php
namespace Craft;

class SproutEmail_SentEmailInfoTableModel extends BaseModel
{
	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'emailType'               => array(AttributeType::String, 'default' => null),
			'mailer'                  => array(AttributeType::String, 'default' => null),
			'testEmail'               => array(AttributeType::String, 'default' => null),
			'senderName'              => array(AttributeType::String, 'default' => null),
			'senderEmail'             => array(AttributeType::String, 'default' => null),
			'source'                  => array(AttributeType::String, 'default' => null),
			'sourceVersion'           => array(AttributeType::String, 'default' => null),
			'craftVersion'            => array(AttributeType::String, 'default' => null),
			'ipAddress'               => array(AttributeType::String, 'default' => null),
			'userAgent'               => array(AttributeType::String, 'default' => null),
			'protocol'                => array(AttributeType::String, 'default' => null),
			'hostName'                => array(AttributeType::String, 'default' => null),
			'port'                    => array(AttributeType::String, 'default' => null),
			'smtpSecureTransportType' => array(AttributeType::String, 'default' => null),
			'timeout'                 => array(AttributeType::String, 'default' => null)
		);
	}
}