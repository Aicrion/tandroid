# Broadcasts and System-Wide Events

`Broadcast\BroadcastDispatcher` is the equivalent of Android's
`BroadcastReceiver` mechanism: it announces system-wide events (not
a direct reply to a specific Update) to every interested plugin —
without the sender and receiver knowing about each other directly.

## Creating an Event

Any plain object (POJO) can be an event; it just needs to be passed
to `publish()`:

```php
namespace Aicrion\Tandroid\Broadcast\Event;

final class UserJoinedEvent
{
    public function __construct(
        public readonly int $userId,
        public readonly int $chatId,
    ) {}
}
```

## Writing a Receiver

```php
use Aicrion\Tandroid\Attribute\BroadcastFilter;
use Aicrion\Tandroid\Broadcast\BroadcastReceiverInterface;
use Aicrion\Tandroid\Broadcast\Event\UserJoinedEvent;
use Aicrion\Tandroid\Api\Telegram;

#[BroadcastFilter(event: UserJoinedEvent::class)]
final class WelcomeReceiver implements BroadcastReceiverInterface
{
    public function onReceive(object $event): void
    {
        /** @var UserJoinedEvent $event */
        Telegram::message()->to($event->chatId)->text('Welcome to the community! 🎉')->send();
    }
}
```

Like any Activity, a Receiver must be declared in the plugin's
`manifest.php`:

```php
return Manifest::define(
    package: 'greeter',
    version: '1.0.0',
    receivers: [WelcomeReceiver::class],
    // ...
);
```

## How It Works

1. On `Kernel::boot()`,
   `BroadcastDispatcher::registerReceivers()` is called with every
   Receiver collected from all manifests. For each Receiver, its
   `#[BroadcastFilter]` is read via Reflection and stored in an
   internal registry (`event class => [receiver classes]`).
2. Whenever your code calls
   `BroadcastDispatcher::publish($event)`, the dispatcher resolves
   every Receiver registered for `$event::class` from the DI
   Container and runs `onReceive($event)` on each of them.

## Built-in Event: UserJoinedEvent

`Kernel::handle()` publishes this event itself: with every Update, it
checks whether the `chat_id` has already been seen in cache (key
`aicrion.seen_chat.{chat_id}`); if it's the first time,
`UserJoinedEvent` is published and the chat is marked as seen. This
means any plugin can react to a "new user" without touching the
framework core — just write a Receiver for `UserJoinedEvent`.

## Publishing Your Own Events

From inside any Activity or service that has access to
`BroadcastDispatcher` (by type-hinting it in the constructor — it's
autowired automatically):

```php
final class OrderActivity extends BotActivity
{
    public function __construct(
        private readonly BroadcastDispatcher $broadcaster,
    ) {}

    public function onCreate(Intent $intent): ?NavigationRequest
    {
        // ... order placement logic

        $this->broadcaster->publish(new OrderPlacedEvent(orderId: $order->id));

        $this->setContentView(View::message('Your order has been placed ✅'));

        return null;
    }
}
```

## Current Limitation

The Broadcast system is currently **synchronous** — `publish()` runs
every Receiver immediately, within the same request. For heavy
operations (sending email, long-running processing) that shouldn't
slow down the reply to the user, write your Receiver to dispatch the
heavy work to a separate queue (e.g. Symfony Messenger, already
available as a dependency) and return quickly.