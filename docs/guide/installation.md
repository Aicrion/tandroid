# Installation

## Requirements

- PHP **8.5** or higher (the framework uses `readonly` properties,
  backed enums, and `declare(strict_types=1)` everywhere).
- PHP extensions: `pdo`, `mbstring`, `json`, and — if you use Redis —
  either the `redis` extension or `predis/predis` (already installed
  as a dependency, so the native extension isn't required).
- Composer 2.x.
- A Doctrine DBAL-supported database: SQLite, MySQL/MariaDB, or
  PostgreSQL. For a quick start, use SQLite — no separate server
  required, and it works well on shared hosting.

## Installing with Composer

```bash
composer require aicrion/tandroid
```

This pulls in Symfony DependencyInjection, EventDispatcher, Cache,
Config, Yaml, Messenger, HttpClient, as well as Doctrine ORM/DBAL/
Migrations and `predis/predis` — no other manual dependencies needed.

## Host Application Structure

The framework is designed as a library, not a project skeleton. A
typical host application looks like this:

```
my-bot/
├── config/
│   └── aicrion.yaml        # main configuration
├── plugins/
│   └── my-plugin/          # every bot feature is a plugin
│       ├── manifest.php
│       └── src/
├── public/
│   └── webhook.php         # webhook mode entry point
├── bin/
│   └── poll.php             # polling mode entry point
├── var/
│   ├── cache/               # filesystem cache (fallback)
│   └── data.sqlite          # default SQLite database
├── vendor/
└── composer.json
```

You can copy this very repository as a starter skeleton;
`public/webhook.php`, `bin/poll.php`, and `config/aicrion.yaml` are
ready to go, and the `plugins/greeter` sample plugin shows what a
real plugin looks like.

## Setting Up the Bot Token

1. Create a bot with [@BotFather](https://t.me/BotFather) and copy
   the token.
2. Set it as an environment variable (recommended — never hardcode
   the token in `aicrion.yaml`):

```bash
export AICRION_BOT_TOKEN="123456:ABC-your-token"
```

3. Check `config/aicrion.yaml` (it already works out of the box
   because it uses `%env(...)%`):

```yaml
bot:
  token: '%env(AICRION_BOT_TOKEN)%'
  mode: webhook # webhook | polling
```

The full config system is documented in [Configuration](configuration.md).

## Running Your First Bot

With the sample `greeter` plugin bundled in the skeleton, you can
test immediately:

### Polling Mode (for local development)

```bash
php bin/poll.php
```

This runs an infinite loop with a 200ms pause between each
`getUpdates` call; press `Ctrl+C` to stop.

### Webhook Mode (for production / shared hosting)

1. Upload `public/` to a server with valid HTTPS.
2. Register the webhook with Telegram:

```bash
curl "https://api.telegram.org/bot$AICRION_BOT_TOKEN/setWebhook?url=https://your-domain.com/webhook.php"
```

or using the framework's `WebhookManager` class:

```php
use Aicrion\Tandroid\Api\Telegram;

Telegram::webhook()->set('https://your-domain.com/webhook.php');
```

Send `/start` to the bot and `plugins/greeter/src/StartActivity.php`
will fire, replying with a welcome message and a "My Profile" button.

## Next Step

The [Kernel and Boot Lifecycle](architecture.md) chapter explains
exactly what happens when `Kernel::boot()` is called.
