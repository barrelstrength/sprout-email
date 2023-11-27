<?php

namespace BarrelStrength\SproutEmail;

use BarrelStrength\Sprout\core\db\MigrationHelper;
use BarrelStrength\Sprout\core\db\SproutPluginMigrationInterface;
use BarrelStrength\Sprout\core\db\SproutPluginMigrator;
use BarrelStrength\Sprout\core\editions\Edition;
use BarrelStrength\Sprout\core\modules\Modules;
use BarrelStrength\Sprout\mailer\MailerModule;
use BarrelStrength\Sprout\sentemail\SentEmailModule;
use BarrelStrength\Sprout\transactional\TransactionalModule;
use Craft;
use craft\base\Plugin;
use craft\db\MigrationManager;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\UrlHelper;
use yii\base\Event;

class SproutEmail extends Plugin implements SproutPluginMigrationInterface
{
    public string $minVersionRequired = '4.4.10';

    public string $schemaVersion = '4.44.445';

    public static function editions(): array
    {
        return [
            Edition::LITE,
            Edition::PRO,
        ];
    }

    public static function getSchemaDependencies(): array
    {
        return [
            MailerModule::class, // Install first
            TransactionalModule::class,
            SentEmailModule::class,
        ];
    }

    public function getMigrator(): MigrationManager
    {
        return SproutPluginMigrator::make($this);
    }

    public function init(): void
    {
        parent::init();

        Event::on(
            Modules::class,
            Modules::INTERNAL_SPROUT_EVENT_REGISTER_AVAILABLE_MODULES,
            static function(RegisterComponentTypesEvent $event) {
                $event->types[] = TransactionalModule::class;
                $event->types[] = SentEmailModule::class;
            }
        );

        $this->instantiateSproutModules();
        $this->grantModuleEditions();
    }

    protected function instantiateSproutModules(): void
    {
        SentEmailModule::isEnabled() && SentEmailModule::getInstance();
        TransactionalModule::isEnabled() && TransactionalModule::getInstance();
    }

    protected function grantModuleEditions(): void
    {
        if ($this->edition === Edition::PRO) {
            SentEmailModule::isEnabled() && SentEmailModule::getInstance()->grantEdition(Edition::PRO);
            TransactionalModule::isEnabled() && TransactionalModule::getInstance()->grantEdition(Edition::PRO);
        }
    }

    protected function afterInstall(): void
    {
        MigrationHelper::runMigrations($this);

        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        // Redirect to welcome page
        $url = UrlHelper::cpUrl('sprout/welcome/transactional-email');
        Craft::$app->getResponse()->redirect($url)->send();
    }

    protected function beforeUninstall(): void
    {
        MigrationHelper::runUninstallMigrations($this);
    }
}
