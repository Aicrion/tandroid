# The Kernel and the Boot Lifecycle

`Kernel\Kernel` is the equivalent of Android's Zygote + System
Server combined: the single place that builds every framework
subsystem, wires it together, and prepares it to process Updates.

## Two Lines to Boot

```php
use Aicrion\Tandroid\Kernel\Kernel;

$kernel = Kernel::fromConfigFile(__DIR__ . '/config/aicrion.yaml')->boot();
```

`fromConfigFile()` reads the YAML file via
`Config\FrameworkConfig::fromFile()`. `boot()` performs every step
below, in this exact order:

## What Happens Inside boot()

1. **Build the DI Container.** A
   `Symfony\Component\DependencyInjection\ContainerBuilder` is
   created.
2. **Register `FrameworkConfig`.** Registered as a synthetic service
   so any class (including plugin Activities and Repositories) can
   type-hint it in their constructor and receive it automatically.
3. **Build the Cache Pool.** `Cache\CachePoolFactory::create()`
   builds either a Redis+filesystem chain or filesystem-only pool
   based on `redis_dsn` (service `aicrion.cache`).
4. **Configure the HTTP Client and Telegram calls.** A
   `Symfony\Component\HttpClient\HttpClient` is built and connected
   to the static `Telegram` facade via `Api\Telegram::configure()` —
   from this point on, `Telegram::message()`, `Telegram::photo()`,
   and every other facade method work.
5. **Discover plugins.** `Package\PackageManager::discover()` reads
   every `plugins/*/manifest.php` and adds it to the manifest list.
6. **Run pending migrations automatically.**
   `PackageManager::runPendingMigrations()` invokes
   `Package\MigrationRunner` for each plugin — no CLI command
   required. Full details in
   [Database and Doctrine](database-and-doctrine.md).
7. **Build the EntityManager.**
   `Database\EntityManagerFactory::create()` uses the list of
   entities from every plugin (`Manifest::$entities`) to build a
   single `Doctrine\ORM\EntityManagerInterface`, registered in the
   container as a synthetic service.
8. **Register Activities.** `FallbackActivityMarker` (the built-in
   "404 Activity") is always registered; then every Activity declared
   in `Manifest::$activities` is registered with `autowire()`.
9. **Wire the navigation core.** `IntentResolver` (with the
   IntentFilter registry collected from every manifest),
   `BackStackStore`, `ViewModel\StateStore`, and finally
   `ActivityManager` are built.
10. **Wire Broadcasts.** `Broadcast\BroadcastDispatcher` is built,
    and every Receiver declared in `Manifest::$receivers` is
    registered with `autowire()` and wired into the dispatcher.
11. **`compile()`.** The container is frozen; immediately after,
    synthetic services (`FrameworkConfig` and
    `EntityManagerInterface`) are set with their real instances
    (this can't be done before `compile()`).

After `boot()`, the Kernel instance is ready to process Updates via
`handle()`.

## Processing an Update

```php
$view = $kernel->handle($update);
```

`handle()` performs these steps:

1. If this is the first time this `chat_id` has been seen, the
   `Broadcast\Event\UserJoinedEvent` event is published (see
   [Broadcasts](broadcasts.md) for details).
2. `ActivityManager::dispatch($update)` is called — this method
   resolves the appropriate Intent, instantiates the target
   Activity, runs its lifecycle, and if a `NavigationRequest` is
   returned, follows the navigation chain right then and there (see
   [Activities and Intents](activities-and-intents.md)).
3. If a `View` was produced, it is sent immediately through
   `Api\Telegram::message()` to the same chat — the host code
   (`webhook.php`/`bin/poll.php`) doesn't need to send the reply
   itself.

Important: `Kernel::handle()` both runs the Activity and delivers
the reply. If you want to control the reply yourself (e.g. for
logging or testing), you can work directly with
`ActivityManager::dispatch()`, which only returns the `View` without
sending it.

## ActivityManager — the Heart of the Kernel

`Kernel\ActivityManager` is the equivalent of Android's
`ActivityManagerService`. Its responsibilities:

- **Resolving the Intent.** Via `IntentResolver` — either explicit
  (from `callback_data`) or implicit (based on registered
  `#[IntentFilter]`s).
- **Managing the lifecycle.** Calling `onCreate`/`onNewIntent`/
  `onResume` in the correct order, and `onPause` on the previous
  Activity.
- **Following the navigation chain.** If a lifecycle hook returns a
  `NavigationRequest` (whether from `startActivity()` or
  `finishWithResult()`), `ActivityManager` immediately follows it —
  within the same incoming Update, with no extra round-trip to
  Telegram. The chain depth is limited to
  `MAX_CHAIN_DEPTH = 8` to prevent infinite loops.
- **Injecting the StateStore.** If an Activity uses the
  `Activity\HasViewModel` trait, `ActivityManager` automatically
  injects `StateStore`, and persists the ViewModel after every
  lifecycle call.
- **Managing the Back Stack.** Via `BackStackStore` (backed by
  cache), keeping each user's stack so the virtual Back button and
  returning to a previous Activity work correctly.

## Overall Flow of an Update

```
Update (Webhook/Polling)
        │
        ▼
   Kernel::handle()
        │
        ▼
 ActivityManager::dispatch()
        │
        ▼
  IntentResolver::resolve()  ──▶ explicit or implicit Intent
        │
        ▼
   instantiate(Activity) from the DI Container
        │
        ▼
 onCreate/onNewIntent → onResume
        │
        ├── non-null NavigationRequest? ──▶ continue the chain (recursively)
        │
        ▼
   BackStackStore::push()
        │
        ▼
     View is returned
        │
        ▼
   Kernel sends a real message to Telegram
```
