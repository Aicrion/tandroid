# Aicrion\Tandroid Documentation

The complete reference for a Telegram bot framework built around an
Android-inspired architecture. This documentation is written
Laravel-style: every chapter is self-contained, but the order below
is the recommended learning path.

## Getting Started

- [Installation](01-installation.md)
- [Configuration](02-configuration.md)

## Core Architecture

- [The Kernel and the Boot Lifecycle](03-architecture.md)
- [Activities and Intents](04-activities-and-intents.md)
- [Views and Widgets](05-views-and-widgets.md)
- [ViewModel and State Management](06-viewmodel-and-state.md)

## Data and Infrastructure

- [Database and Doctrine](07-database-and-doctrine.md)
- [Caching and Redis](08-caching-and-redis.md)

## Extensibility

- [Plugin System (Package Manager)](09-plugins.md)
- [Broadcasts and System-Wide Events](10-broadcasts.md)

## Connecting to Telegram

- [Webhook and Polling Modes](11-transport-webhook-and-polling.md)
- [Complete Telegram API Reference](12-telegram-api-reference.md)

## Other Features

- [Localization (i18n)](13-localization.md)
- [Testing](14-testing.md)
- [Deployment: Shared Hosting and VPS](15-deployment.md)
- [Advanced Features: Remote Intents, Guest Mode, Managed Bots](16-advanced-features.md)

## Quick Reference

| Android concept | Aicrion\Tandroid equivalent | Docs |
|---|---|---|
| `Activity` | `Activity\BotActivity` | [Activities and Intents](04-activities-and-intents.md) |
| `Intent` / `IntentFilter` | `Intent\Intent` / `Attribute\IntentFilter` | [Activities and Intents](04-activities-and-intents.md) |
| `ActivityManagerService` | `Kernel\ActivityManager` | [The Kernel](03-architecture.md) |
| `PackageManagerService` | `Package\PackageManager` | [Plugins](09-plugins.md) |
| `AndroidManifest.xml` | each plugin's `Package\Manifest` | [Plugins](09-plugins.md) |
| `View` / `Widget` | `View\View` / `Widget\*` | [Views and Widgets](05-views-and-widgets.md) |
| `androidx.lifecycle.ViewModel` | `Kernel\ViewModel\ViewModel` | [ViewModel](06-viewmodel-and-state.md) |
| `BroadcastReceiver` | `Attribute\BroadcastFilter` | [Broadcasts](10-broadcasts.md) |
| Back Stack | `Kernel\BackStackStore` | [The Kernel](03-architecture.md) |
| Navigation Deep Links | `Kernel\DeepLinkResolver` | [Activities and Intents](04-activities-and-intents.md) |

## Found a bug?

If the framework's actual behavior doesn't match what's written here,
treat it as a bug — this documentation is meant to always be an
accurate reflection of the real code in `src/`, not architectural
aspirations.
