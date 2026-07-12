# Building Your First Bot: Full Walkthrough

This chapter is a single, start-to-finish tutorial. If you read only
one page in this documentation, read this one — by the end, you will
have a real, working Telegram bot with two Activities, navigation
between them, a keyboard, saved state, and both run modes (polling
and webhook) tested locally.

Every other chapter in this documentation explains a *concept* in
isolation (Activities, Widgets, ViewModel, ...). This chapter shows
how those concepts fit together in an actual project, step by step.

## Step 1 — Create the Project Folder

Tandroid is a library, not a project generator, so you start with an
empty folder and add the pieces yourself:

```bash
mkdir my-telegram-bot && cd my-telegram-bot
composer init --name=you/my-telegram-bot --type=project --no-interaction
```

Then require the framework:

```bash
composer require aicrion/tandroid
```

This installs Tandroid and every dependency it needs (Symfony DI,
Cache, HttpClient, VarExporter, Doctrine ORM/DBAL/Migrations,
`predis/predis`) — nothing else to install manually.

## Step 2 — Create the Folder Structure

Create the following empty folders and files; this is the exact
layout the Kernel expects:

```bash
mkdir -p config plugins public bin var
touch config/aicrion.yaml public/webhook.php bin/poll.php
```

Your project should now look like this:

```
my-telegram-bot/
├── config/
│   └── aicrion.yaml
├── plugins/
├── public/
│   └── webhook.php
├── bin/
│   └── poll.php
├── var/
├── vendor/
└── composer.json
```

## Step 3 — Get a Bot Token

Open Telegram, talk to [@BotFather](https://t.me/BotFather), send
`/newbot`, follow the prompts, and copy the token it gives you (it
looks like `123456789:AAExampleTokenAbcDefGhiJklMno`).

Export it as an environment variable — never commit it to
`aicrion.yaml` directly:

```bash
export AICRION_BOT_TOKEN="123456789:AAExampleTokenAbcDefGhiJklMno"
```

On Windows (PowerShell):

```powershell
$env:AICRION_BOT_TOKEN="123456789:AAExampleTokenAbcDefGhiJklMno"
```

## Step 4 — Write the Configuration File

Open `config/aicrion.yaml` and paste:

```yaml
bot:
  token: '%env(AICRION_BOT_TOKEN)%'
  mode: polling # switch to 'webhook' later, for production

cache:
  redis_dsn: '%env(AICRION_REDIS_DSN)%' # empty is fine — falls back to filesystem

database:
  driver: pdo_sqlite
  path: var/data.sqlite

locale: en
plugins_path: plugins
```

Nothing else needs to be touched — every subsystem (cache, database,
locale, HTTP client) is built from this single file. Full reference
in [Configuration](configuration.html).

## Step 5 — Create Your First Plugin

Every feature in Tandroid — even the very first "Hello World" — is a
**plugin**. Create the folder structure:

```bash
mkdir -p plugins/greeter/src
```

Create `plugins/greeter/manifest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Plugins\Greeter;

use Aicrion\Tandroid\Package\Manifest;

return Manifest::define(
    package: 'greeter',
    version: '1.0.0',
    activities: [
        StartActivity::class,
        ProfileActivity::class,
    ],
    entities: [],
    receivers: [],
);
```

> Manifests are plain PHP files that `return` a `Manifest` object —
> exactly like `AndroidManifest.xml`, but there's no XML to parse.
> Full details in [Plugin System](plugins.html).

## Step 6 — Write Your First Activity

Create `plugins/greeter/src/StartActivity.php`:

```php
<?php

declare(strict_types=1);

namespace App\Plugins\Greeter;

use Aicrion\Tandroid\Activity\BotActivity;
use Aicrion\Tandroid\Activity\NavigationRequest;
use Aicrion\Tandroid\Attribute\IntentFilter;
use Aicrion\Tandroid\Intent\Intent;
use Aicrion\Tandroid\View\View;
use Aicrion\Tandroid\Widget\{Button, Keyboard};

#[IntentFilter(action: 'MAIN', category: 'LAUNCHER')]
final class StartActivity extends BotActivity
{
    public function onCreate(Intent $intent): ?NavigationRequest
    {
        $keyboard = Keyboard::inline()
            ->row(Button::action('👤 My Profile', to: ProfileActivity::class));

        $this->setContentView(
            View::message('Welcome to my first Tandroid bot! 👋')
                ->attach($keyboard),
        );

        return null;
    }
}
```

What is happening here:

- `#[IntentFilter(action: 'MAIN', category: 'LAUNCHER')]` tells the
  framework: "run this Activity whenever the user sends `/start` or
  any plain message with no other match" — exactly like an Android
  app's launcher Activity.
- `onCreate()` is the only method you are required to implement.
- `setContentView()` declares what gets sent back to the user.
- `Button::action(...)` builds an inline button whose tap will be
  routed, as an explicit Intent, straight to `ProfileActivity`.

## Step 7 — Write a Second Activity and Navigate to It

Create `plugins/greeter/src/ProfileActivity.php`:

```php
<?php

declare(strict_types=1);

namespace App\Plugins\Greeter;

use Aicrion\Tandroid\Activity\BotActivity;
use Aicrion\Tandroid\Activity\NavigationRequest;
use Aicrion\Tandroid\Intent\Intent;
use Aicrion\Tandroid\View\View;
use Aicrion\Tandroid\Widget\{Button, Keyboard};

final class ProfileActivity extends BotActivity
{
    public function onCreate(Intent $intent): ?NavigationRequest
    {
        $userId = $this->update()->userId;

        $keyboard = Keyboard::inline()
            ->row(Button::action('⬅️ Back', to: StartActivity::class));

        $this->setContentView(
            View::message("Your Telegram user ID is: {$userId}")
                ->attach($keyboard),
        );

        return null;
    }
}
```

Notice `ProfileActivity` has **no** `#[IntentFilter]` — it doesn't
need one, because it's only ever reached *explicitly*, via the
`callback_data` produced by `Button::action(..., to: ProfileActivity::class)`
in `StartActivity`. This is exactly the explicit-vs-implicit split
described in [Activities and Intents](activities-and-intents.html).

## Step 8 — Run It Locally with Polling

Open `bin/poll.php` and paste:

```php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Aicrion\Tandroid\Kernel\Kernel;
use Aicrion\Tandroid\Update\PollingUpdateSource;

$kernel = Kernel::fromConfigFile(__DIR__ . '/../config/aicrion.yaml')->boot();
$source = new PollingUpdateSource($kernel->httpClient(), $kernel->config()->botToken);

echo "Bot is running. Press Ctrl+C to stop.\n";

while (true) {
    foreach ($source->pull() as $update) {
        try {
            $kernel->handle($update);
        } catch (\Throwable $e) {
            fwrite(STDERR, "Error: {$e->getMessage()}\n");
        }
    }

    usleep(200_000);
}
```

Run it:

```bash
php bin/poll.php
```

Open Telegram, find your bot, and send `/start`. You should get the
welcome message with a "My Profile" button; tapping it should show
your numeric user ID with a "Back" button that returns you to the
welcome screen. **That round trip — Start → Profile → Back — is the
entire back-stack and navigation system working end to end.**

## Step 9 — Switch to Webhook Mode (Production)

Polling works great locally but wastes resources in production.
Switch `config/aicrion.yaml`:

```yaml
bot:
  mode: webhook
```

Fill in `public/webhook.php`:

```php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Aicrion\Tandroid\Kernel\Kernel;
use Aicrion\Tandroid\Update\WebhookUpdateSource;

$kernel = Kernel::fromConfigFile(__DIR__ . '/../config/aicrion.yaml')->boot();
$update = (new WebhookUpdateSource())->read();

if ($update !== null) {
    $kernel->handle($update);
}
```

Upload `public/`, `vendor/`, `src` (if any), `plugins/`, `config/`,
and `var/` to a server with HTTPS, then tell Telegram where to send
updates:

```bash
curl "https://api.telegram.org/bot$AICRION_BOT_TOKEN/setWebhook?url=https://your-domain.com/webhook.php"
```

Send `/start` again — this time Telegram is pushing the Update to
your server instead of you pulling it. Full details, including
shared-hosting caveats, in
[Webhook and Polling Modes](transport-webhook-and-polling.html) and
[Deployment](deployment.html).

## Step 10 — Persist State with a ViewModel

Right now, nothing survives between requests. Let's make
`ProfileActivity` remember how many times a user has visited it.

Add the `HasViewModel` trait and a `ViewModel` subclass:

```php
<?php

declare(strict_types=1);

namespace App\Plugins\Greeter;

use Aicrion\Tandroid\Kernel\ViewModel\ViewModel;

final class ProfileViewModel extends ViewModel
{
    public int $visitCount = 0;
}
```

```php
<?php

declare(strict_types=1);

namespace App\Plugins\Greeter;

use Aicrion\Tandroid\Activity\BotActivity;
use Aicrion\Tandroid\Activity\HasViewModel;
use Aicrion\Tandroid\Activity\NavigationRequest;
use Aicrion\Tandroid\Intent\Intent;
use Aicrion\Tandroid\View\View;
use Aicrion\Tandroid\Widget\{Button, Keyboard};

final class ProfileActivity extends BotActivity
{
    use HasViewModel;

    public function onCreate(Intent $intent): ?NavigationRequest
    {
        /** @var ProfileViewModel $viewModel */
        $viewModel = $this->viewModel(ProfileViewModel::class);
        $viewModel->visitCount++;

        $keyboard = Keyboard::inline()
            ->row(Button::action('⬅️ Back', to: StartActivity::class));

        $this->setContentView(
            View::message("Visits to this screen: {$viewModel->visitCount}")
                ->attach($keyboard),
        );

        return null;
    }
}
```

The `HasViewModel` trait transparently wires a `StateStore` (cache
backed) so `visitCount` survives across separate incoming Updates —
no manual serialization required. Full details in
[ViewModel and State Management](viewmodel-and-state.html).

## Step 11 — Write a Test

Create `tests/StartActivityTest.php` (requires `phpunit/phpunit`,
already installed as a dev dependency):

```php
<?php

declare(strict_types=1);

namespace App\Tests;

use App\Plugins\Greeter\StartActivity;
use Aicrion\Tandroid\Intent\Intent;
use PHPUnit\Framework\TestCase;

final class StartActivityTest extends TestCase
{
    public function test_it_renders_a_welcome_message(): void
    {
        $activity = new StartActivity();
        $navigation = $activity->onCreate(Intent::to(StartActivity::class));

        self::assertNull($navigation);
        self::assertStringContainsString(
            'Welcome',
            $activity->getContentView()?->render()['text'] ?? '',
        );
    }
}
```

```bash
composer test
```

Full testing guide, including how to test navigation *chains*
(startActivity → startActivity → ... → View), is in
[Testing](testing.html).

## What You Just Built

- A real project skeleton, wired entirely through `config/aicrion.yaml`.
- A plugin (`greeter`) with two Activities and explicit navigation
  between them via `Button::action()`.
- Both run modes tested locally: polling for development, webhook
  for production.
- Persisted per-user state with a `ViewModel`.
- A working PHPUnit test.

From here, every other chapter in the sidebar goes deeper on one of
these building blocks — Views/Widgets, the Kernel's boot lifecycle,
Doctrine entities, caching, plugins with migrations, Broadcasts,
localization, and the full Telegram API surface.
