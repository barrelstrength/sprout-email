<?php

namespace BarrelStrength\SproutEmail\migrations;

use BarrelStrength\Sprout\core\db\m000000_000000_sprout_plugin_migration;
use BarrelStrength\Sprout\core\db\SproutPluginMigrationInterface;
use BarrelStrength\SproutEmail\SproutEmail;

class m240530_000000_schema_5_0_0 extends m000000_000000_sprout_plugin_migration
{
    public function getPluginInstance(): SproutPluginMigrationInterface
    {
        return SproutEmail::getInstance();
    }
}
