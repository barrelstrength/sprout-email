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

		$styleTags = array();

		$stylesResult = sproutEmail()->defaultmailer->addPlaceholderStyleTags($body, $styleTags);

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

		$this->assertEquals($expected['body'], $stylesResult);

		$replacedBody = sproutEmail()->defaultmailer->removePlaceholderStyleTags($expected['body'], $styleTags);

		$this->assertEquals($expected['tags'], $styleTags);
		$this->assertEquals($expected['body'], $stylesResult);

		$replacedBody = sproutEmail()->defaultmailer->removePlaceholderStyleTags($stylesResult, $styleTags);

		$this->assertEquals($body, $replacedBody);
	}

	public function testCommaEmailInput()
	{
		$invalidInput = "email@valid.com, 1email@.com, emailinvalid.com";

		$result = sproutEmail()->getValidAndInvalidRecipients($invalidInput);

		$expected = array("email@valid.com", "1email@.com", "emailinvalid.com");

		$this->assertEquals($expected, $result['emails']);

		$expected = array("1email@.com", "emailinvalid.com");

		$this->assertEquals($expected, $result['invalid']);

		$object = new SproutEmail_SimpleRecipientModel;
		$object->email = "email@valid.com";

		$expected = array($object);

		$this->assertEquals($expected, $result['valid']);
	}

	public function testEventIds()
	{
		$importer = new SproutEmail_EntriesSaveEntryEvent;
		$importer->setPluginName('SproutEmail');

		$selectId = $importer->getEventId();

		$expected = "SproutEmail:entries-saveEntry";

		$this->assertEquals($expected, $selectId);
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
