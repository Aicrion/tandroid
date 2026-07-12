<p align="center">
  <img src="docs/assets/logo.svg" width="72" alt="Tandroid logo" onerror="this.style.display='none'">
</p>

<h1 align="center">Tandroid</h1>

<p align="center">
  <strong>An Android-inspired framework for building Telegram bots in PHP.</strong>
</p>

<p align="center">
  <a href="https://packagist.org/packages/aicrion/tandroid"><img src="https://img.shields.io/badge/php-%3E%3D8.5-777bb4" alt="PHP Version"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue" alt="License"></a>
  <a href="https://github.com/aicrion/tandroid/actions"><img src="https://img.shields.io/badge/tests-passing-brightgreen" alt="Tests"></a>
  <a href="https://aicrion.github.io/tandroid"><img src="https://img.shields.io/badge/docs-online-01696f" alt="Docs"></a>
</p>

<br>

Tandroid brings Android's application model to Telegram bot
development. Every feature is an **Activity**, navigation between
features is an **Intent**, every installable feature is a **plugin**
with its own manifest, and state survives across requests via a
**ViewModel** — concepts you already know, applied to a domain where
they've never quite existed before.

Built on Symfony (DI, Cache, HTTP Client) and Doctrine ORM, Tandroid
runs equally well on a $3/month shared host or a Dockerized VPS —
migrations and plugin discovery happen automatically, with zero CLI
commands required in production.

## Why Tandroid

- **A real application model, not a router.** Activities have a
  lifecycle (`onCreate`, `onResume`, `onPause`, `onDestroy`), a back
  stack, and explicit/implicit Intent resolution — the same mental
  model as native Android apps.
- **Plugins, not spaghetti.** Every feature lives in its own
  `plugins/<name>/` folder with an isolated manifest, entities, and
  migrations — install, remove, or share a feature without touching
  the rest of the bot.
- **Stateful conversations, done right.** `ViewModel` + `StateStore`
  persist structured state between Activities and requests, so
  multi-step flows (forms, wizards, checkouts) stay simple.
- **Zero-ops deployment.** Migrations run automatically on boot.
  Redis is optional — the cache layer falls back to the filesystem
  transparently, so shared hosting just works.
- **Full Telegram Bot API coverage.** Messaging, media, payments &
  Stars, inline mode, forums, business accounts, gifts, and more —
  behind one fluent facade.

## Quick Start

```bash
composer require aicrion/tandroid
```

```php
use Aicrion\Tandroid\Activity\{BotActivity, NavigationRequest};
use Aicrion\Tandroid\Attribute\IntentFilter;
use Aicrion\Tandroid\Intent\Intent;
use Aicrion\Tandroid\View\View;

#[IntentFilter(action: 'MAIN', category: 'LAUNCHER')]
final class StartActivity extends BotActivity
{
    public function onCreate(Intent $intent): ?NavigationRequest
    {
        $this->setContentView(View::message('Welcome 👋'));

        return null;
    }
}
```

```php
use Aicrion\Tandroid\Kernel\Kernel;

$kernel = Kernel::fromConfigFile(__DIR__ . '/config/aicrion.yaml')->boot();
$kernel->handle($update); // resolves the Activity and sends the reply
```

## Documentation

Full documentation — installation, architecture, Activities & Intents,
Views & Widgets, ViewModels, database, caching, plugins, broadcasts,
deployment, and the complete API reference — is available at:

**[aicrion.github.io/tandroid](https://aicrion.github.io/tandroid)**

## Testing

```bash
composer test
```

## Contributing

Issues and pull requests are welcome. Please open an issue first for
significant changes, and make sure `composer test` passes before
submitting a PR.

## License

Tandroid is open-sourced software licensed under the [MIT license](LICENSE).
