# Aicrion\Tandroid

A framework for building Telegram bots with an architecture inspired
by the Android operating system: every bot feature is a
`BotActivity`, navigation between features happens via `Intent`, and
every plugin is an "installed app" with its own independent
`manifest.php`. Built on PHP 8.5+, Symfony DependencyInjection/
HttpClient/Cache, and Doctrine ORM/Migrations.

**📖 Full documentation: [`docs/guide/00-index.html`](docs/guide/00-index.html)**
(installation, architecture, Activity/Intent, View/Widget, ViewModel,
database, caching, plugins, Broadcasts, Webhook/Polling, full API
reference, i18n, testing, deployment, and advanced features — each
chapter self-contained and precise.)

## Core Architecture (Quick Look)

| Android concept | Aicrion\Tandroid equivalent |
|---|---|
| Activity | `Activity\BotActivity` |
| Intent / IntentFilter | `Intent\Intent` / `Attribute\IntentFilter` |
| ActivityManagerService | `Kernel\ActivityManager` |
| PackageManagerService | `Package\PackageManager` |
| AndroidManifest.xml | `Package\Manifest` (per plugin) |
| View / Widget | `View\View` / `Widget\*` |
| androidx.lifecycle.ViewModel | `Kernel\ViewModel\ViewModel` / `StateStore` |
| BroadcastReceiver | `Attribute\BroadcastFilter` + `Broadcast\BroadcastDispatcher` |
| Back Stack | `Kernel\BackStackStore` (backed by Redis/filesystem cache) |

## Quick Start

```bash
composer require aicrion/tandroid
```

```php
use Aicrion\Tandroid\Kernel\Kernel;

$kernel = Kernel::fromConfigFile(__DIR__ . '/config/aicrion.yaml')->boot();
$kernel->handle($update); // runs the matching Activity and sends the reply automatically
```

Full installation/configuration/run details in
[Installation](docs/guide/01-installation.html).

## A Small Activity

```php
use Aicrion\Tandroid\Activity\{BotActivity, NavigationRequest};
use Aicrion\Tandroid\Attribute\IntentFilter;
use Aicrion\Tandroid\Intent\Intent;
use Aicrion\Tandroid\View\View;
use Aicrion\Tandroid\Widget\{Button, Keyboard};

#[IntentFilter(action: 'MAIN', category: 'LAUNCHER')]
final class StartActivity extends BotActivity
{
    public function onCreate(Intent $intent): ?NavigationRequest
    {
        $this->setContentView(
            View::message('Welcome 👋')
                ->attach(Keyboard::inline()->row(Button::action('My Profile', to: ProfileActivity::class))),
        );

        return null;
    }
}
```

More in [Activities and Intents](docs/guide/04-activities-and-intents.html).

## Building a New Plugin

Every plugin lives in `plugins/<package-name>/` and includes
`manifest.php`, its own Activities, and — if needed — a
`migrations/` folder for Doctrine Migrations, which run automatically
on the first request after deployment — no CLI command required. A
complete example is available at `plugins/greeter/`; the full guide
is at [Plugin System](docs/guide/09-plugins.html).

## Telegram Bot API Coverage

Messaging, media (photo/video/document/audio/...), group and forum
topic management, payments and Stars, Rich Messages, reactions,
Inline mode, Business accounts, gifts and stickers, join requests,
Bot-to-Bot, Guest Mode, and Managed Bots — all available through the
fluent `Api\Telegram` facade. The full table is in the
[Telegram API Reference](docs/guide/12-telegram-api-reference.html).

```php
Telegram::message()->to($chatId)->text('Hello from Aicrion')->send();
Telegram::photo()->to($chatId)->media($fileIdOrUrl)->caption('Your product 📦')->send();
Telegram::chat($chatId)->ban($userId);
Telegram::invoice()->to($chatId)->title('Premium Subscription')->payload('sub_monthly')->priceInStars(150)->send();
```

## Testing

```bash
vendor/bin/phpunit
```

A guide to writing new tests (including testing Activity navigation
chains) is at [Testing](docs/guide/14-testing.html).
