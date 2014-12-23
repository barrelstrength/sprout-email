<?php
namespace Craft;

use Mockery as m;

class SproutEmailServiceTest extends SproutEmailBaseTest
{
	public function testServicesIsInitializedAndTestsCanBeRan()
	{
		$this->assertInstanceOf('\\Craft\\SproutEmailService', sproutEmail());
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
