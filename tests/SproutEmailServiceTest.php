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
		$array = array(2, 4, 5);

		$result = sproutEmail()->mailers->isArraySettingsMatch($array, $options);

		$this->assertEquals($expected, $result);
	}

	public function testGetStyleTags()
	{

		$body = '
						<html>
							<head>
								<style> .red { color: red }</style>
								<style> .green { color: green }</style>
							</head>
							<body>
								<h1>testbody</h1>
							</body>
						</html>';

		$stylesResult = sproutEmail()->defaultmailer->getStyleTags($body);

		$expected = array(
			'tags' => array(
				'<!-- %style0% -->' => "<style> .red { color: red }</style>",
				'<!-- %style1% -->' => "<style> .green { color: green }</style>"
			),
		  'body' => '
						<html>
							<head>
								<!-- %style0% -->
								<!-- %style1% -->
							</head>
							<body>
								<h1>testbody</h1>
							</body>
						</html>'
		);
		$this->assertEquals($expected, $stylesResult);

		$replacedBody = sproutEmail()->defaultmailer->replaceActualStyles($expected['body'], $stylesResult['tags']);

		$this->assertEquals($body, $replacedBody);


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
