# Aicrion\Tandroid Documentation

The complete reference for a Telegram bot framework built around an
Android-inspired architecture. This documentation is written
Laravel-style: every chapter is self-contained, but the order below
is the recommended learning path.

## Getting Started

- [Installation](installation.md)
- [Configuration](configuration.md)

## Core Architecture

- [The Kernel and the Boot Lifecycle](architecture.md)
- [Activities and Intents](activities-and-intents.md)
- [Views and Widgets](views-and-widgets.md)
- [ViewModel and State Management](viewmodel-and-state.md)

## Data and Infrastructure

- [Database and Doctrine](database-and-doctrine.md)
- [Caching and Redis](caching-and-redis.md)

## Extensibility

- [Plugin System (Package Manager)](plugins.md)
- [Broadcasts and System-Wide Events](broadcasts.md)

## Connecting to Telegram

- [Webhook and Polling Modes](transport-webhook-and-polling.md)
- [Complete Telegram API Reference](telegram-api-reference.md)

## Other Features

- [Localization (i18n)](localization.md)
- [Testing](testing.md)
- [Deployment: Shared Hosting and VPS](deployment.md)
- [Advanced Features: Remote Intents, Guest Mode, Managed Bots](advanced-features.md)

## Quick Reference

| Android concept | Aicrion\Tandroid equivalent | Docs |
|---|---|---|
| `Activity` | `Activity\BotActivity` | [Activities and Intents](activities-and-intents.md) |
| `Intent` / `IntentFilter` | `Intent\Intent` / `Attribute\IntentFilter` | [Activities and Intents](activities-and-intents.md) |
| `ActivityManagerService` | `Kernel\ActivityManager` | [The Kernel](architecture.md) |
| `PackageManagerService` | `Package\PackageManager` | [Plugins](plugins.md) |
| `AndroidManifest.xml` | each plugin's `Package\Manifest` | [Plugins](plugins.md) |
| `View` / `Widget` | `View\View` / `Widget\*` | [Views and Widgets](views-and-widgets.md) |
| `androidx.lifecycle.ViewModel` | `Kernel\ViewModel\ViewModel` | [ViewModel](viewmodel-and-state.md) |
| `BroadcastReceiver` | `Attribute\BroadcastFilter` | [Broadcasts](broadcasts.md) |
| Back Stack | `Kernel\BackStackStore` | [The Kernel](architecture.md) |
| Navigation Deep Links | `Kernel\DeepLinkResolver` | [Activities and Intents](activities-and-intents.md) |

## Found a bug?

If the framework's actual behavior doesn't match what's written here,
treat it as a bug — this documentation is meant to always be an
accurate reflection of the real code in `src/`, not architectural
aspirations.
