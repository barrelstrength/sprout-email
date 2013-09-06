<?php

namespace Craft;

use Twig_Extension;
use Twig_Filter_Method;

class MasterBlasterTwigExtension extends Twig_Extension
{
    public function getName()
    {
        return 'Master Blaster';
    }

    public function getFilters()
    {
        return array(
            'wordwrap' => new Twig_Filter_Method($this, 'wordwrapFilter'),
        );
    }

    // This doesn't work properly yet, but we need to get it to support tags like this:
    // 
    // {% wordwrap %}
    // {% endwordwrap %}
    // 
    public function wordwrapFilter($value, $length = 80, $separator = "\n", $preserve = false)
    {

        $sentences = array();

        $previous = mb_regex_encoding();
        mb_regex_encoding($env->getCharset());

        $pieces = mb_split($separator, $value);
        mb_regex_encoding($previous);

        foreach ($pieces as $piece) {
            while(!$preserve && mb_strlen($piece, $env->getCharset()) > $length) {
                $sentences[] = mb_substr($piece, 0, $length, $env->getCharset());
                $piece = mb_substr($piece, $length, 2048, $env->getCharset());
            }

            $sentences[] = $piece;
        }

        return implode($separator, $sentences);
    }
}
