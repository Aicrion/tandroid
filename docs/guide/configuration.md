# Configuration System

All framework configuration is read from a single YAML file
(`config/aicrion.yaml`) and exposed to every subsystem as an
immutable object (`Config\FrameworkConfig`); nowhere else in the code
calls `getenv()` or reads the YAML file directly.

## Config File Structure

```yaml
bot:
  token: '%env(AICRION_BOT_TOKEN)%'
  mode: webhook # webhook | polling

cache:
  redis_dsn: '%env(AICRION_REDIS_DSN)%' # if empty, cache automatically falls back to the filesystem

database:
  driver: pdo_sqlite
  path: var/data.sqlite

locale: en
plugins_path: plugins
```

## The FrameworkConfig Class

`Config\FrameworkConfig::fromFile(string $path)` reads the YAML file
and returns a `readonly` instance of itself:

| Property | Type | Description |
|---|---|---|
| `botToken` | `string` | The bot token, after `%env(...)%` values are resolved |
| `updateMode` | `string` | `webhook` or `polling` |
| `redisDsn` | `?string` | Redis DSN; if `null`/empty, only the filesystem cache is used |
| `database` | `array` | An array directly compatible with `Doctrine\DBAL\DriverManager::getConnection()` |
| `plugins` | `array` | Per-plugin configuration (optional, at each plugin's discretion) |
| `locale` | `string` | The default locale for `I18n\Translator` |
| `pluginsPath` | `string` | Path to the plugins folder, relative to the running script |

## Overriding with Environment Variables

Any value written with the `%env(NAME)%` syntax is replaced with
`getenv('NAME')` at load time. This pattern is directly inspired by
Symfony's Environment Variable Processors, but the implementation is
lighter (`FrameworkConfig::resolveEnv`) and only resolves plain
strings — no complex processors like `%env(json:...)%`.

```yaml
bot:
  token: '%env(AICRION_BOT_TOKEN)%'
```

If the environment variable isn't set, `null` is returned (not the
raw placeholder string).

## Database Configuration

The `database` section is passed straight to
`Doctrine\DBAL\DriverManager::getConnection()`, so it supports
anything DBAL accepts:

```yaml
# SQLite (default — no server required)
database:
  driver: pdo_sqlite
  path: var/data.sqlite

# MySQL / MariaDB
database:
  driver: pdo_mysql
  host: 127.0.0.1
  port: 3306
  dbname: aicrion
  user: aicrion
  password: '%env(DB_PASSWORD)%'
  charset: utf8mb4

# PostgreSQL
database:
  driver: pdo_pgsql
  host: 127.0.0.1
  port: 5432
  dbname: aicrion
  user: aicrion
  password: '%env(DB_PASSWORD)%'
```

The practical usage of this connection (Entities, Repositories,
Migrations) is covered in
[Database and Doctrine](database-and-doctrine.md).

## Cache/Redis Configuration

```yaml
cache:
  redis_dsn: '%env(AICRION_REDIS_DSN)%'
```

If `redis_dsn` is empty, or the Redis connection fails at boot time,
`Cache\CachePoolFactory` silently and automatically falls back to
using only the filesystem adapter (`var/cache`) — this is exactly
what lets it work on shared hosting without Redis. Full details in
[Caching and Redis](caching-and-redis.md).

## Plugins Path

```yaml
plugins_path: plugins
```

`Package\PackageManager::discover()` scans this path with the
pattern `{plugins_path}/*/manifest.php`. For projects with a
different layout (e.g. `apps/` instead of `plugins/`), just change
this value.

## Accessing Config from Your Own Code

If you need config values inside an Activity or service, get them
through the DI container (which auto-injects into any autowired
service, since `FrameworkConfig` itself is registered in the
container) or via `Kernel::config()`:

```php
$kernel = Kernel::fromConfigFile(__DIR__ . '/config/aicrion.yaml')->boot();
$locale = $kernel->config()->locale;
```