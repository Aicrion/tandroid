<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Database;

use Aicrion\Tandroid\Config\FrameworkConfig;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;

/**
 * Builds the single Doctrine EntityManager shared by the whole
 * installation. Metadata mapping paths are derived automatically
 * from every plugin's declared entities (Manifest::$entities) via
 * reflection — plugin authors never configure Doctrine directly,
 * they just list their entity classes in manifest.php and the
 * Kernel wires the rest, exactly like MigrationRunner does for
 * schema migrations.
 */
final class EntityManagerFactory
{
    /**
     * @param list<class-string> $entityClasses every entity declared by every installed plugin
     */
    public static function create(FrameworkConfig $config, array $entityClasses): EntityManagerInterface
    {
        $paths = [];

        foreach ($entityClasses as $entityClass) {
            $file = (new \ReflectionClass($entityClass))->getFileName();

            if ($file !== false) {
                $paths[] = \dirname($file);
            }
        }

        $ormConfig = ORMSetup::createAttributeMetadataConfiguration(
            paths: array_values(array_unique($paths)),
            isDevMode: true,
        );

        $connection = DriverManager::getConnection($config->database, $ormConfig);

        return new EntityManager($connection, $ormConfig);
    }
}