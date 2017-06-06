<?php

namespace Craft;

use Twig_Extension;
use Twig_Filter_Method;
use Twig_SimpleFilter;

class SproutEmailTwigExtension extends Twig_Extension
{
	/**
	 * @var array
	 */
	protected $settings = array();

	/**
	 * @param string $html
	 *
	 * @return string
	 */
	public function htmlEntityDecode($html = '')
	{
		return html_entity_decode($html, null, craft()->templates->getTwig()->getCharset());
	}

	/**
	 * @return array
	 */
	public function getFilters()
	{
		return array(
			'wordwrap'         => new Twig_Filter_Method($this, 'wordwrapFilter'),
			'htmlEntityDecode' => new Twig_SimpleFilter('htmlEntityDecode', array($this, 'htmlEntityDecode'), array('is_safe', true)),
		);
	}

	/**
	 * @return array
	 */
	public function getFunctions()
	{
		return array(
			'htmlEntityDecode' => new Twig_SimpleFilter('htmlEntityDecode', array($this, 'htmlEntityDecode'), array('is_safe', true))
		);
	}

	/**
	 * @todo - Add wordwrap feature
	 *         This doesn't work properly yet. Update Sprout Email to support tags like this.
	 *
	 * Wordwrap filter used the following Twig Extension as a starting point but has updated the
	 * filter to chop out <p> tags and <br> tags, add \n breaks, and to not cut words off when
	 * wrapping lines.  Lines will wrap at the closest breaking space before the character limit.
	 *
	 * https://github.com/fabpot/Twig-extensions/blob/master/lib/Twig/Extensions/Extension/Text.php
   *
	 * {% wordwrap %}
	 * {% endwordwrap %}
   *
	 * @param        $value
	 * @param int    $length
	 * @param string $separator
	 * @param bool   $preserve
	 *
	 * @return string
	 */
	public function wordwrapFilter($value, $length = 60, $separator = "\n", $preserve = false)
	{
		$lines = array();

		$value = str_replace("<p>", "", $value);
		$value = str_replace("</p>", "\n\n", $value);
		$value = preg_replace("/<br\W*?\/>/", "\n", $value);

		if (substr($value, -2) == "\n\n")
		{
			$value = substr($value, 0, -2);
		}

		$previous = mb_regex_encoding();
		mb_regex_encoding(craft()->templates->getTwig()->getCharset());

		$pieces = mb_split($separator, $value);
		mb_regex_encoding($previous);

		foreach ($pieces as $piece)
		{
			while (!$preserve && mb_strlen($piece, craft()->templates->getTwig()->getCharset()) > $length)
			{
				$count   = count($lines);
				$lines[] = mb_substr($piece, 0, $length, craft()->templates->getTwig()->getCharset());

				$lastSpacePosition = strrpos($lines[$count], ' ', 0);
				$finalCharacters   = trim(substr($lines[$count], $lastSpacePosition, 60));
				$lines[$count]     = substr($lines[$count], 0, $lastSpacePosition);

				$piece = $finalCharacters . $piece;
				$piece = mb_substr($piece, $length, 2048, craft()->templates->getTwig()->getCharset());
			}

			$lines[] = $piece;
		}

		return implode($separator, $lines);
	}
}
