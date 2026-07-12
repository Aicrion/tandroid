# Plugin System (Package Manager)

Every bot feature is written as an independent **plugin** — exactly
like every app installed on Android has its own Activities,
permissions, and database. `Package\PackageManager` is the
equivalent of `PackageManagerService`.

## Plugin Structure

```
plugins/greeter/
├── manifest.php              # this plugin's AndroidManifest.xml
├── migrations/
│   ├── migrations.php
│   └── versions/
│       └── Version20260101000000.php
└── src/
    ├── StartActivity.php
    ├── ProfileActivity.php
    ├── RelayActivity.php
    ├── WelcomeReceiver.php
    ├── Entity/
    │   └── Subscriber.php
    └── Repository/
        └── SubscriberRepository.php
```

## Writing manifest.php

```php
use Aicrion\Tandroid\Package\Manifest;
use Aicrion\Tandroid\Package\Permission;
use Greeter\Entity\Subscriber;
use Greeter\{ProfileActivity, RelayActivity, StartActivity, WelcomeReceiver};

return Manifest::define(
    package: 'greeter',
    version: '1.0.0',
    activities: [StartActivity::class, ProfileActivity::class, RelayActivity::class],
    permissions: [Permission::SendMessage, Permission::BroadcastEvents],
    entities: [Subscriber::class],
    receivers: [WelcomeReceiver::class],
);
```

| Field | Android equivalent | Role |
|---|---|---|
| `package` | `package="..."` | The plugin's unique identifier; also used to name the migration table |
| `version` | `versionName` | Purely informational; not currently checked for compatibility/auto-update |
| `activities` | `<activity>` | **Any Activity not declared here is never registered in the DI Container and can never be navigated to** — even with a `#[IntentFilter]` on it |
| `permissions` | `<uses-permission>` | A declarative list from the `Permission` enum; currently documentation/audit only, not yet enforced automatically at runtime |
| `entities` | — | The list of Entity classes; used to build the EntityManager automatically and resolve migration paths |
| `receivers` | `<receiver>` | Classes implementing `Broadcast\BroadcastReceiverInterface` |

> ⚠️ Important: the only way to activate an Activity, Receiver, or
> Entity is to declare it in that same plugin's `manifest.php`.
> Simply having the class exist in `src/` isn't enough — this is a
> deliberate design so `PackageManager` only wires autoloading and DI
> for things that are actually "installed."

## The Discovery Cycle

`PackageManager::discover()` runs on every `Kernel::boot()`:

```php
$pattern = "{plugins_path}/*/manifest.php";

foreach (glob($pattern) as $manifestFile) {
    $manifest = require $manifestFile; // must return a Manifest
}
```

After discovery, `Kernel::boot()` continues in this order:

1. Runs each plugin's pending migrations
   ([Database and Doctrine](07-database-and-doctrine.md)).
2. Builds the `EntityManager` using the Entities collected from all
   plugins.
3. `autowire`s every declared Activity in the DI Container.
4. Builds the `#[IntentFilter]` registry for `IntentResolver`.
5. Registers every declared Receiver in `BroadcastDispatcher`.

## Automatic Autoloading — No Separate composer.json

Plugins aren't installed as Composer dependencies (only their folder
is copied), so Composer has no idea their classes exist.
`PackageManager::discover()` registers a lightweight PSR-4-style
autoloader for every discovered plugin via `spl_autoload_register()`:
the first namespace segment (which must match the plugin's root
class name exactly, e.g. `Greeter`) maps directly to that plugin's
`src/` folder:

```
Greeter\StartActivity              → plugins/greeter/src/StartActivity.php
Greeter\Entity\Subscriber          → plugins/greeter/src/Entity/Subscriber.php
Greeter\Repository\SubscriberRepository → plugins/greeter/src/Repository/SubscriberRepository.php
```

This means your plugin's `src/` folder structure just needs to match
its internal namespaces under this convention — the exact same one
Composer's PSR-4 uses — without needing to build a separate
`composer.json` per plugin or run `composer dump-autoload`.

## Installing a New Plugin

Installing a plugin means simply **copying its folder into
`plugins/`**:

```bash
cp -r /path/to/awesome-plugin plugins/awesome-plugin
```

On the next request (webhook or polling), `Kernel::boot()` will:

- discover the new plugin,
- build its tables (automatic migration),
- activate its Activities and Receivers.

No install command, no manual class cache, no SSH required — the
same experience as installing an APK where the OS handles the rest.

## Removing a Plugin

Delete its folder from `plugins/`. Its Activities/Receivers will no
longer resolve. **Its database tables are not automatically
removed** (much like uninstalling an Android app without
`adb uninstall -k` sometimes keeps app data) — if you need a full
cleanup, write a `down()` migration for it and run it before
deleting the folder.

## Suggested Skeleton for Your Next Plugin

The best starting point is copying `plugins/greeter` and renaming
the namespace — this plugin demonstrates every core pattern (a
simple Activity, an Activity with navigation, an Activity making a
bot-to-bot call, Entity+Migration, a Receiver) in the smallest
possible form.
