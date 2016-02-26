<?php
namespace Craft;

require 'SproutEmailBaseTest.php';

class SproutEmailServiceTest extends SproutEmailBaseTest
{
	public function testServicesIsInitializedAndTestsCanBeRan()
	{
		$this->assertInstanceOf('\\Craft\\SproutEmailService', sproutEmail());
	}

	public function checkboxOptionProvider()
	{
		$array = array('one', 'two');
		return array(
			array(null, '*'),
			array('*', '*'),
			array($array, $array)
		);
	}

	/**
	 *
	 * @dataProvider checkboxOptionProvider
	 */
	public function testCheckboxSelectFieldValue($option, $expected)
	{
		$value = sproutEmail()->mailers->getCheckboxFieldValue($option);
		$this->assertEquals($expected, $value);
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
