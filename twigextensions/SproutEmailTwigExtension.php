<?php
namespace Craft;

use Twig_Extension;
use Twig_SimpleFilter;

class SproutEmailTwigExtension extends Twig_Extension
{
	protected $settings = array();

	public function getName()
	{
		return 'Sprout Email Filters';
	}

	public function htmlEntityDecode($html='')
	{
		return html_entity_decode($html, null, craft()->templates->getTwig()->getCharset());
	}

	public function getFilters()
	{
		return array(
			'htmlEntityDecode'		=> new Twig_SimpleFilter('htmlEntityDecode', array($this, 'htmlEntityDecode'), array('is_safe', true)),
		);
	}

	public function getFunctions()
	{
		return array(
			'htmlEntityDecode'	=> new Twig_SimpleFilter('htmlEntityDecode', array($this, 'htmlEntityDecode'), array('is_safe', true))
		);
	}
}
