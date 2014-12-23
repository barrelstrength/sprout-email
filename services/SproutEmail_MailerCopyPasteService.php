<?php
namespace Craft;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

class SproutEmail_MailerCopyPasteService extends BaseApplicationComponent
{
	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @var Client
	 */
	protected $httpClient;

	public function init()
	{
		// Load dependencies
		$this->httpClient = new Client;

		require_once craft()->path->getPluginsPath().'sproutemail/libraries/CopyPaste/CopyPaste/newsletter.php';
	}

	public function setSettings(array $settings)
	{
		$this->settings = $settings;
	}

	public function setHttpClient(ClientInterface $httpClient)
	{
		$this->httpClient = $httpClient;
	}

	public function getSubscriberList()
	{
		$subscriberLists = array();

		$CopyPaste = new \CopyPasteNewsletter($this->settings['apiUser'], $this->settings['apiKey']);

		$result = $CopyPaste->newsletter_lists_get();

		if (!$result)
		{
			return $subscriberLists;
		}

		foreach ($result as $v)
		{
			$subscriberLists [$v['list']] = $v['list'];
		}

		return $subscriberLists;
	}

	public function exportEntry($campaign = array(), $listIds = null)
	{
		$data        = array();
		$domain      = craft()->request->getHostInfo();
		$endpoint    = $campaign['templateCopyPaste'];
		$queryString = 'entryId='.$campaign['entryId'];

		$textResponse = $this->httpClient->get(sprintf('%s/%s.txt?%s', $domain, $endpoint, $queryString));
		$htmlResponse = $this->httpClient->get(sprintf('%s/%s?%s', $domain, $endpoint, $queryString));

		$data['text'] = trim($textResponse->getBody());
		$data['html'] = trim($htmlResponse->getBody());

		$data['title']     = $campaign['title'];
		$data['fromName']  = $campaign['fromName'];
		$data['fromEmail'] = $campaign['fromEmail'];

		echo json_encode($data);
		craft()->end();
	}
}
