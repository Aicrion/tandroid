# Database and Doctrine

The framework uses Doctrine ORM/DBAL/Migrations for its data layer.
Each plugin owns its own Entities, and each plugin's migrations are
tracked and run **completely independently** of other plugins —
exactly like each Android app's separate data tables under
`/data/data/<package>/`.

## Defining an Entity

Entities are defined with standard Doctrine PHP 8 attributes —
nothing framework-specific here:

```php
namespace Greeter\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \Greeter\Repository\SubscriberRepository::class)]
#[ORM\Table(name: 'greeter_subscribers')]
class Subscriber
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'bigint', unique: true)]
    private int $chatId;

    #[ORM\Column(type: 'string', length: 8)]
    private string $locale = 'en';

    // ... getters/setters
}
```

Every Entity must be declared in the plugin's `manifest.php` (see
[Plugins](09-plugins.md)):

```php
return Manifest::define(
    package: 'greeter',
    version: '1.0.0',
    entities: [\Greeter\Entity\Subscriber::class],
    // ...
);
```

## How the EntityManager Is Built

On every `Kernel::boot()`, `Database\EntityManagerFactory` collects
the list of all Entities declared across **every** installed
manifest, resolves each Entity's folder via Reflection, and builds a
single shared `Doctrine\ORM\EntityManagerInterface` — the exact
instance registered in the DI Container, which any
Repository/service can receive automatically just by type-hinting it
in its constructor:

```php
namespace Greeter\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Greeter\Entity\Subscriber;

final class SubscriberRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function findByChatId(int $chatId): ?Subscriber
    {
        return $this->entityManager->getRepository(Subscriber::class)
            ->findOneBy(['chatId' => $chatId]);
    }

    public function save(Subscriber $subscriber): void
    {
        $this->entityManager->persist($subscriber);
        $this->entityManager->flush();
    }
}
```

## Support for Multiple Database Types

Any database Doctrine DBAL supports is usable via the `database`
section in `config/aicrion.yaml`: SQLite, MySQL/MariaDB, PostgreSQL.
The format is documented in [Configuration](02-configuration.md).
There's no driver-specific code in the framework — everything flows
through Doctrine's standard `DriverManager::getConnection()`.

## Automatic Migrations — No CLI Command Required

This is one of the main differences from a typical Doctrine project:
**pending migrations run automatically on every `boot()`**, exactly
like installing an Android app that builds/updates its internal
database schema on first run.

```
plugins/greeter/
├── manifest.php
├── migrations/
│   ├── migrations.php          # Doctrine Migrations config for this plugin
│   └── versions/
│       └── Version20260101000000.php
└── src/
```

### The `migrations/migrations.php` File

This is a standard
`Doctrine\Migrations\Configuration\Migration\PhpFile` file — an
array specifying the migrations path and the **tracking table name
specific to this plugin**:

```php
return [
    'migrations_paths' => ['Greeter\\Migrations' => __DIR__ . '/versions'],
    'table_storage' => [
        'table_name' => 'aicrion_migrations_greeter',
    ],
];
```

Because each plugin has its own migration tracking table
(`aicrion_migrations_<package>`), two plugins never collide on
migration version numbering — exactly like each Android app having
its own isolated namespace.

### `Package\MigrationRunner`

`PackageManager::runPendingMigrations()` builds a `MigrationRunner`
for each plugin, which:

1. Builds the DBAL connection from `FrameworkConfig::$database`.
2. Loads that plugin's `migrations.php` config via
   `Doctrine\Migrations\Configuration\Migration\PhpFile`.
3. Builds a ready-to-run instance with
   `DependencyFactory::fromConnection()`.
4. Calls `Migrator::migrate()` (to the latest version) — no
   interactive prompt, suitable for automatic execution in the
   middle of an HTTP request on shared hosting.

Result: just drop the plugin into `plugins/` and send a request to
the bot (or run `bin/poll.php`) — that plugin's tables are created
automatically.

### Writing a Migration

```php
namespace Greeter\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260101000000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('greeter_subscribers');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('chat_id', 'bigint', ['unique' => true]);
        $table->addColumn('locale', 'string', ['length' => 8, 'default' => 'en']);
        $table->setPrimaryKey(['id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('greeter_subscribers');
    }
}
```

## Production Performance Note

`EntityManagerFactory` runs with `isDevMode: true` by default (i.e.
Doctrine metadata isn't cached, and attributes are re-read on every
boot). For high traffic in production, add a metadata caching layer
(APCu/filesystem) between `EntityManagerFactory` and your own
project, or keep the result of `boot()` alive at the process level
(a long-running worker instead of one-request-per-process).
