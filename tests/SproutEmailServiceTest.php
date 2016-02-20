<?php
namespace Craft;

require 'SproutEmailBaseTest.php';

use \Mockery as m;

class SproutEmailServiceTest extends SproutEmailBaseTest
{
	public function testServicesIsInitializedAndTestsCanBeRan()
	{
		$this->assertInstanceOf('\\Craft\\SproutEmailService', sproutEmail());
	}

	public function testSentInfo()
	{
		$mockInfo = array('Mailer' => 'Mailchimp', 'Email Type' => 'Notification');

		$mock = m::mock('\Craft\SproutEmail_SentEmailsService[getSentEmailInfo]')
				->shouldReceive('getSentEmailInfo')
				->andReturn($mockInfo)
				->mock();

		$infoRow = $mock->getInfoRow(1);

		$expectedString = 'data-inforow=\'[{"label":"Mailer","attribute":"mailer"},{"label":"Email Type","attribute":"emailtype"}]\'';
		$expectedString.=" data-mailer='Mailchimp' data-emailtype='Notification'";

		$this->assertEquals($expectedString,$infoRow);
	}

	/**
	 * ENVIRONMENT
	 * -----------
	 */
	public function setUp()
	{
		parent::setUp();

	}
}
