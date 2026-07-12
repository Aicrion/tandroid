# Advanced Features: Remote Intents, Guest Mode, Managed Bots

This chapter covers three higher-level features built on top of
Telegram Bot API version 9.6/10.0+. If the Bot API version you're
working with doesn't support these methods, feel free to ignore
these sections — the rest of the framework works fine without them.

## Bot-to-Bot Communication (Remote Intent)

`Remote\RemoteIntent` is the cross-process equivalent of `Intent`:
instead of navigating to a local Activity, it addresses a
structured message to another bot (by username).

```php
use Aicrion\Tandroid\Remote\{RemoteIntent, BotToBotClient};

$remote = RemoteIntent::to('copywriter_bot', 'DRAFT_REQUEST')
    ->with('brief', 'Write ad copy for Product X')
    ->with('reply_to_chat', $chatId);

(new BotToBotClient($httpClient, $token))->send($remote);
```

On the receiving side, the incoming bot-to-bot message is decoded
back into a `RemoteIntent` with `BotToBotClient::decodeIncoming()`,
and a local Intent is built from it:

```php
$remoteIntent = BotToBotClient::decodeIncoming($rawIncomingMessage);
$localIntent = Intent::to(DraftRequestActivity::class)
    ->putExtra('brief', $remoteIntent->toArray()['payload']['brief']);
```

This is exactly the pattern shown in
`plugins/greeter/src/RelayActivity.php`: it takes a human request and
relays it to another specialist bot.

## Guest Mode

When a user invokes the bot via `@mention` inside a chat the bot
hasn't been added to, `Guest\GuestContext` holds the constraints of
that interaction:

```php
final class GuestContext
{
    public readonly int $hostChatId;
    public readonly int $mentioningUserId;
    public readonly bool $isGuestInvocation;
    public readonly ?string $guestQueryId;
}
```

Two important rules:

1. **Before any operation that assumes full membership in the chat**
   (checking admin status, reading the member list), call
   `$guestContext->assertFullAccess()` — if the interaction really is
   a guest invocation, this method throws a `RuntimeException` to
   prevent incorrect access.
2. **A reply in guest mode should not be sent with a plain
   `sendMessage`** — since the bot doesn't have persistent membership
   in that chat. Instead:

```php
Telegram::guestQuery($guestContext->answerQueryId())
    ->reply('This is your reply as a guest mention.');
```

> Currently, building a `GuestContext` from a raw Update (detecting
> whether it's actually a guest mention) is part of your host
> project's logic — this class only provides the data structure and
> safety rules; wiring it automatically into `ActivityManager` is
> still on the roadmap (see "Known Limitations" below).

## Managed Bots

`Managed\BotFactory` lets a parent bot create independent child bots
for each customer/tenant — instead of routing everything through a
single shared token:

```php
use Aicrion\Tandroid\Managed\BotFactory;

$factory = new BotFactory($httpClient, parentToken: $parentBotToken);

$childBot = $factory->createManagedBot(name: "Ali's Shop Bot", username: 'ali_shop_bot');
// $childBot->botId, $childBot->username, $childBot->token

$factory->rotateToken($childBot->botId); // safe token rotation
$factory->revoke($childBot->botId);      // fully remove the child bot
```

Every `Managed\ManagedBot` is a simple object carrying an ID/
username/token. To actually run it as a fully independent instance,
build a separate `Kernel` with a `FrameworkConfig` specific to that
token:

```php
$childConfig = FrameworkConfig::fromFile(__DIR__ . '/config/aicrion.yaml')->withBotToken($childBot->token);
$childKernel = new Kernel($childConfig)->boot();
```

## Known Limitations (Honestly Stated)

These three features work fully at the HTTP level (sending/receiving
requests to the Bot API), but they are **not yet automatically wired
into `Kernel`/`ActivityManager`** — meaning:

- Automatically detecting that an incoming Update is a guest mention
  or a bot-to-bot message, and automatically building a
  `GuestContext`/`RemoteIntent` from it, is currently your code's
  responsibility (you can add this logic in a custom `UpdateMapper`
  or a middleware layer before `Kernel::handle()`).
- Running multiple `ManagedBot`s concurrently usually means multiple
  processes/webhook entry points (each with its own config); the
  framework doesn't yet have a built-in orchestrator for running
  multiple Kernels in a single process.

These limitations are documented intentionally so this section
accurately reflects the code's real behavior, not an idealized
architecture.