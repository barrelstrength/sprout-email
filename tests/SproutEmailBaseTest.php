<?php
namespace Craft;

use Mockery as m;

class SproutEmailBaseTest extends BaseTest
{
	/**
	 * @var \Mockery\MockInterface
	 */
	protected $config;

	/**
	 * ENVIRONMENT
	 * -----------
	 */
	public function setUp()
	{
		$this->autoload();

		$this->config = m::mock('Craft\ConfigService');

		$this->config->shouldReceive('usePathInfo')->andReturn(true);
		$this->config->shouldReceive('getIsInitialized')->andReturn(true);
		$this->config->shouldReceive('omitScriptNameInUrls')->andReturn(true);

		$this->config->shouldReceive('get')->with('user', 'db')->andReturn('root');
		$this->config->shouldReceive('get')->with('password', 'db')->andReturn('secret');
		$this->config->shouldReceive('get')->with('database', 'db')->andReturn('sandboxdev');
		$this->config->shouldReceive('get')->with('devMode')->andReturn(false);
		$this->config->shouldReceive('get')->with('cpTrigger')->andReturn('admin');
		$this->config->shouldReceive('get')->with('baseCpUrl')->andReturn('http://sandbox.dev/');
		$this->config->shouldReceive('get')->with('pageTrigger')->andReturn('p');
		$this->config->shouldReceive('get')->with('actionTrigger')->andReturn('action');
		$this->config->shouldReceive('get')->with('usePathInfo')->andReturn(true);
		$this->config->shouldReceive('get')->with('translationDebugOutput')->andReturn(false);

		$this->config->shouldReceive('getLocalized')->with('loginPath')->andReturn('login');
		$this->config->shouldReceive('getLocalized')->with('logoutPath')->andReturn('logout');
		$this->config->shouldReceive('getLocalized')->with('setPasswordPath')->andReturn('setpassword');
		$this->config->shouldReceive('getLocalized')->with('siteUrl')->andReturn('http://sandbox.dev');

		$this->config->shouldReceive('getCpLoginPath')->andReturn('login');
		$this->config->shouldReceive('getCpLogoutPath')->andReturn('logout');
		$this->config->shouldReceive('getCpSetPasswordPath')->andReturn('setpassword');
		$this->config->shouldReceive('getResourceTrigger')->andReturn('resource');

		$this->config->shouldReceive('get')->with('slugWordSeparator')->andReturn('-');
		$this->config->shouldReceive('get')->with('allowUppercaseInSlug')->andReturn(false);
		$this->config->shouldReceive('get')->with('addTrailingSlashesToUrls')->andReturn(true);

		$this->setComponent(craft(), 'config', $this->config);

		$mainService          = new SproutEmailService();
		$campaignService      = new SproutEmail_CampaignsService();
		$campaignEntryService = new SproutEmail_EntriesService();
		$notificationsService = new SproutEmail_NotificationsService();

		$this->setComponent(craft(), 'sproutEmail_campaign', $campaignService);
		$this->setComponent(craft(), 'sproutEmail_campaignEntry', $campaignEntryService);
		$this->setComponent(craft(), 'sproutEmail_notifications', $notificationsService);

		$mainService->init();
		$this->setComponent(craft(), 'sproutEmail', $mainService);

		$plugin = new SproutEmailPlugin();
		$plugin->init();

		$pluginService = m::mock('Craft\PluginsService[getPlugin]');
		$pluginService->shouldReceive('getPlugin')->with('sproutemail')->andReturn($plugin);

		$this->setComponent(craft(), 'plugins', $pluginService);
		$this->setComponent(craft(), 'sproutEmail_campaign', new SproutEmail_CampaignsService());
		$this->setComponent(craft(), 'sproutEmail_campaignEntry', new SproutEmail_EntriesService());
		$this->setComponent(craft(), 'sproutEmail_notifications', new SproutEmail_NotificationsService());
	}

	public function tearDown()
	{
		m::close();
	}

	protected function autoload()
	{
		$map = array(
			'\\Craft\\SproutEmailPlugin'                => '../SproutEmailPlugin.php',
			'\\Craft\\SproutEmailService'               => '../services/SproutEmailService.php',
			'\\Craft\\SproutEmail_CampaignsService'      => '../services/SproutEmail_CampaignsService.php',
			'\\Craft\\SproutEmail_EntriesService'       => '../services/SproutEmail_EntriesService.php',
			'\\Craft\\SproutEmail_NotificationsService' => '../services/SproutEmail_NotificationsService.php',
			'\\Craft\\SproutEmailVariable'              => '../variables/SproutEmailVariable.php',
			'\\Craft\\SproutEmailTwigExtension'         => '../twigextensions/SproutEmailTwigExtension.php',
			'\\Craft\\SproutEmail_EntryRecord'          => '../records/SproutEmail_EntryRecord.php',
		);

		foreach ($map as $classPath => $filePath)
		{
			if (!class_exists($classPath, false))
			{
				require_once $filePath;
			}
		}
	}
}
