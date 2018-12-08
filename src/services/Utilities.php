<?php

namespace barrelstrength\sproutemail\services;

use craft\base\Component;

class Utilities extends Component
{
    /**
     * Call this method to get singleton
     *
     * @param bool $refresh
     *
     * @return Utilities
     */
    public static function Instance($refresh = false)
    {
        static $inst = null;
        if ($inst === null) {
            $inst = new self();
        }

        return $inst;
    }
}