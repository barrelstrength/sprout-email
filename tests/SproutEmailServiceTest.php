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

	public function optionSettingsProvider()
	{
		return array(
			array(array(1, 4, 3), true),
			array(array(1, 3, 6), false),
			array('*', true),
			array('', false)
		);
	}

	/**
	 *
	 * @dataProvider optionSettingsProvider
	 */
	public function testIsArraySettingsMatch($options, $expected)
	{
		$array   = array(2, 4, 5);

		$result = sproutEmail()->mailers->isArraySettingsMatch($array, $options);

		$this->assertEquals($result, $expected);

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
