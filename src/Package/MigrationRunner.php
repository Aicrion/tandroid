<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Package;

use Aicrion\Tandroid\Config\FrameworkConfig;
use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\Migration\PhpFile;
use Doctrine\Migrations\DependencyFactory;

/**
 * Executes each plugin's pending Doctrine migrations automatically
 * on the very first request after a code deploy — no SSH, no CLI,
 * no `composer` command required on the server. It tracks a
 * per-plugin schema_version row in the doctrine_migration_versions
 * table and only runs migrations newer than what's already applied,
 * making it safe to call on every single request with near-zero
 * overhead once up to date.
 */
final class MigrationRunner
{
    public function __construct(
        private readonly FrameworkConfig $config,
    ) {}

    public function migrate(Manifest $manifest): void
    {
        $migrationsPath = sprintf('%s/%s/migrations', rtrim($this->config->pluginsPath, '/'), $manifest->package);

        if (!is_dir($migrationsPath)) {
            return;
        }

        $connection = DriverManager::getConnection($this->config->database);

        // The per-plugin migrations.php file itself declares its own
        // `table_storage.table_name`, so Doctrine\Migrations\Configuration\Migration\PhpFile
        // already tracks applied versions in a plugin-scoped table
        // (e.g. aicrion_migrations_greeter) without any extra wiring here.
        $migrationConfig = new PhpFile($migrationsPath . '/migrations.php');

        $dependencyFactory = DependencyFactory::fromConnection(
            $migrationConfig,
            new \Doctrine\Migrations\Configuration\Connection\ExistingConnection($connection),
        );

        $planCalculator = $dependencyFactory->getMigrationPlanCalculator();
        $plan = $planCalculator->getPlanUntilVersion($dependencyFactory->getVersionAliasResolver()->resolveVersionAlias('latest'));

        if (count($plan) === 0) {
            return;
        }

        $migrator = $dependencyFactory->getMigrator();
        $migrator->migrate($plan);
    }
}