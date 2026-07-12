<?php

declare(strict_types=1);

/**
 * Doctrine Migrations configuration for the "greeter" plugin.
 * MigrationRunner (see src/Package/MigrationRunner.php) picks this
 * file up automatically at boot and applies any pending version
 * classes below — no CLI command needed, even on shared hosting.
 */
return [
    'migrations_paths' => [
        'Greeter\\Migrations' => __DIR__ . '/versions',
    ],
    'table_storage' => [
        'table_name' => 'aicrion_migrations_greeter',
    ],
];