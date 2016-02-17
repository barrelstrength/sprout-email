<?php
namespace Craft;

require 'SproutEmailBaseTest.php';

class SproutEmailServiceTest extends SproutEmailBaseTest
{
	public function testServicesIsInitializedAndTestsCanBeRan()
	{
		$this->assertInstanceOf('\\Craft\\SproutEmailService', sproutEmail());
	}

	public function testCheckboxSelectFieldValue()
	{
		$options = null;
		$value = sproutEmail()->mailers->getCheckboxFieldValue($options);
		$this->assertEquals('*', $value);

		$options = '*';
		$value = sproutEmail()->mailers->getCheckboxFieldValue($options);
		$this->assertEquals('*', $value);

		$options = '';
		$value = sproutEmail()->mailers->getCheckboxFieldValue($options);
		$this->assertEquals('x', $value);

		$options = array('one', 'two');
		$value = sproutEmail()->mailers->getCheckboxFieldValue($options);

		$this->assertEquals($options, $value);

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
