<?php
namespace Craft;

use Twig_Extension;
use Twig_Filter_Method;
use \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class SproutEmailTwigExtension extends Twig_Extension
{
	public function getName()
	{
		return 'Sprout Email';
	}
	
	public function getFilters()
	{
		return array (
			'inlineCss' => new Twig_Filter_Method( $this, 'inlineCssFilter',  array('is_safe' => array('html')) ),
			'wordwrap' => new Twig_Filter_Method( $this, 'wordwrapFilter'),

			// 'jsonDecode' => new Twig_Filter_Method( $this, 'jsonDecodeFilter' ) 
		);
	}

	/**
	 * Inline CSS
	 * @todo  - make this more robust.  This is just proof of concept right now.
	 * 
	 * @param  [type] $string [description]
	 * @return [type]         [description]
	 */
	public function inlineCssFilter($string)
	{
		$cssToInlineStyles = new CssToInlineStyles();

		$cssToInlineStyles->setHTML($string);	

		// Use styles block
		$cssToInlineStyles->setUseInlineStylesBlock(true);

		$html = $cssToInlineStyles->convert();

		return $html;
	}
	
	// @TODO - This doesn't work properly yet, but we need to 
	// get Email to support tags like this.  Site source of code, or update 
	// to own use case: https://github.com/fabpot/Twig-extensions/blob/master/lib/Twig/Extensions/Extension/Text.php
	//
	// {% wordwrap %}
	// {% endwordwrap %}
	//
	public function wordwrapFilter($value, $length = 60, $separator = "\n", $preserve = false)
	{
		$sentences = array ();
		
		$previous = mb_regex_encoding();
		mb_regex_encoding( craft()->templates->getTwig()->getCharset() );
		
		$pieces = mb_split( $separator, $value );
		mb_regex_encoding( $previous );
		
		foreach ( $pieces as $piece )
		{
			while ( ! $preserve && mb_strlen( $piece, craft()->templates->getTwig()->getCharset() ) > $length )
			{
				$sentences [] = mb_substr( $piece, 0, $length, craft()->templates->getTwig()->getCharset() );
				$piece = mb_substr( $piece, $length, 2048, craft()->templates->getTwig()->getCharset() );
			}
			
			$sentences [] = $piece;
		}
		
		return implode( $separator, $sentences );
	}
	
	// public function jsonDecodeFilter($string)
	// {
	// 	return json_decode( $string );
	// }
}
