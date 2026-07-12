# Webhook and Polling Modes

The framework supports both models Telegram offers for receiving
Updates. The choice between the two depends purely on your
environment — your Activity code is **exactly the same** in both
modes; the only difference is the entry point.

## Comparison

| | Webhook | Polling |
|---|---|---|
| Requires public HTTPS | Yes | No |
| Requires a long-running process | No — every Update is a normal HTTP request | Yes — an infinite loop |
| Suitable for shared hosting | ✅ Yes, the default choice | ❌ Most shared hosts don't allow long-running processes |
| Entry point | `public/webhook.php` | `bin/poll.php` |
| Update source class | `Update\WebhookUpdateSource` | `Update\PollingUpdateSource` |

## Webhook Mode

### Registering the Webhook with Telegram

```php
use Aicrion\Tandroid\Api\Telegram;

Telegram::webhook()->set(
    url: 'https://your-domain.com/webhook.php',
    secretToken: getenv('AICRION_WEBHOOK_SECRET') ?: null,
    dropPendingUpdates: true,
);
```

or directly with `curl`:

```bash
curl "https://api.telegram.org/bot$AICRION_BOT_TOKEN/setWebhook?url=https://your-domain.com/webhook.php"
```

### The `public/webhook.php` Entry Point

```php
$kernel = Kernel::fromConfigFile(__DIR__ . '/../config/aicrion.yaml')->boot();
$source = new WebhookUpdateSource(file_get_contents('php://input') ?: '');

foreach ($source->pull() as $update) {
    $kernel->handle($update); // Kernel itself also sends the reply
}

http_response_code(200);
```

Every time Telegram has a new Update, it sends a `POST` request with
a JSON body directly to this file. `Kernel::boot()` runs once per
HTTP request — on shared hosting this means no persistent process is
needed, exactly like running any regular PHP script.

### Managing the Webhook

```php
Telegram::webhook()->info();               // getWebhookInfo
Telegram::webhook()->delete(dropPendingUpdates: true); // switch back to polling
```

## Polling Mode

### The `bin/poll.php` Entry Point

```bash
php bin/poll.php
```

```php
$kernel = Kernel::fromConfigFile(__DIR__ . '/../config/aicrion.yaml')->boot();
$source = new PollingUpdateSource($kernel->httpClient(), $kernel->config()->botToken);

while (true) {
    foreach ($source->pull() as $update) {
        $kernel->handle($update);
    }

    usleep(200_000);
}
```

`PollingUpdateSource` keeps the `update_id` offset internally, so
each `pull()` only returns new Updates, and restarting the process
won't reprocess old Updates (as long as the process stays alive
long enough; for persistence across restarts, store the offset
yourself in cache/database).

> For stable production execution, run `bin/poll.php` under a
> process supervisor (systemd, supervisord, or Docker's
> `restart: always`) so it automatically comes back up after a
> crash.

## Switching Between Modes

The `bot.mode` value in `config/aicrion.yaml` is purely documentation
of your intent; the entry point you actually run
(`webhook.php` on a web server, or `poll.php` via CLI) determines
the real mode. Never enable both simultaneously for the same bot —
Telegram accepts either Webhooks or `getUpdates`, not both; if
Webhook is active and you want to switch to Polling, call
`Telegram::webhook()->delete()` first.
