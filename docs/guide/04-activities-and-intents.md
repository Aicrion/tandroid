# Activities and Intents

The core programming model of this framework is exactly what
Android calls `Activity` and `Intent`. Every "screen" or "feature"
of the bot is a `BotActivity`; navigation between them happens via
`Intent`.

## Creating an Activity

```php
use Aicrion\Tandroid\Activity\BotActivity;
use Aicrion\Tandroid\Activity\NavigationRequest;
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

Every Activity must implement `onCreate()`. Four other hooks are
optional and default to a no-op implementation: `onResume()`,
`onNewIntent()`, `onPause()`, `onDestroy()`.

## Lifecycle

| Hook | When it's called | Return type |
|---|---|---|
| `onCreate(Intent $intent)` | First entry into this Activity | `?NavigationRequest` (must be implemented) |
| `onNewIntent(Intent $intent)` | This Activity is already on top of the stack and receives a new callback_data (e.g. toggling a checkbox) — without a full `onCreate` cycle | `?NavigationRequest` |
| `onResume()` | After `onCreate`/`onNewIntent`, and also when the user navigates back to this Activity via the Back button | `?NavigationRequest` |
| `onPause()` | Right before another Activity is pushed onto the stack | `void` |
| `onDestroy()` | When this Activity is popped off the stack | `void` |

Key point: since PHP doesn't keep state between requests, every
Activity is **freshly instantiated on every request** — exactly like
Android recreating an Activity on a configuration change (screen
rotation). If you need state that persists across Activities, use a
[ViewModel](06-viewmodel-and-state.md), not regular class properties.

## Navigation Chains with NavigationRequest

`BotActivity` exposes two protected methods to subclasses:

```php
protected function startActivity(Intent $intent): NavigationRequest;
protected function finishWithResult(array $result = []): NavigationRequest;
```

When one of the lifecycle hooks (`onCreate`, `onNewIntent`,
`onResume`) returns a `NavigationRequest` (instead of `null`),
`ActivityManager` immediately follows it — **within the same
incoming Update, with no extra round-trip to Telegram**:

```php
final class GatewayActivity extends BotActivity
{
    public function onCreate(Intent $intent): ?NavigationRequest
    {
        if ($this->userIsAdmin()) {
            return $this->startActivity(Intent::to(AdminPanelActivity::class));
        }

        $this->setContentView(View::message('Access restricted.'));

        return null;
    }
}
```

If `finishWithResult()` is returned, `ActivityManager` behaves like
pressing the Back button: it pops the current Activity off the
stack, restores the previous Activity, and re-runs its `onResume()`.

> The chain depth is capped at 8
> (`ActivityManager::MAX_CHAIN_DEPTH`). If a `View` isn't reached
> within that limit, a `RuntimeException` is thrown — a safeguard
> against infinite navigation loops.

## Intent — Explicit vs. Implicit

`Intent\Intent` has the exact same two modes as
`android.content.Intent`:

```php
// Explicit: directly targets an Activity
Intent::to(ProfileActivity::class);

// Implicit: matched against IntentFilters by action/category
Intent::action('VIEW_PROFILE');
```

Adding extras is fluent and immutable — every `putExtra()` returns a
new instance:

```php
$intent = Intent::to(OrderActivity::class)
    ->putExtra('order_id', 42)
    ->withFlag(IntentFlag::ClearBackStack);
```

### Navigation Flags (IntentFlag)

| Flag | Effect |
|---|---|
| `ClearBackStack` | Clears the user's back stack before pushing the new entry |
| `NewTask` | Behaves like `ClearBackStack` (both reset the stack) |
| `NoHistory` | This entry is never pushed onto the stack at all (a transient Activity) |
| `SingleTop` | Even if this exact Activity is already on top of the stack, a full `onCreate` runs instead of `onNewIntent` |

## IntentFilter — How an Update Reaches an Activity

```php
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class IntentFilter
{
    public function __construct(
        public readonly string $action,
        public readonly ?string $category = null,
        public readonly ?string $pattern = null,
        public readonly int $priority = 0,
    ) {}
}
```

`Kernel\IntentResolver::resolve()` converts an incoming Update into
an Intent like this:

1. **If `callback_data` is valid JSON with an `a` key** (i.e. it came
   from a `Widget\Button::action()`), an **explicit** Intent is built
   targeting that same Activity, and `p` (payload) is added as
   extras.
2. **Otherwise**, every registered `#[IntentFilter]` is checked: if a
   filter has a `pattern`, the message text is matched against that
   regex; otherwise, only filters with `action: 'MAIN'` match regular
   messages. If multiple Activities match, the one with the highest
   `priority` wins.
3. **If no Activity matches**, the Intent is routed to
   `Kernel\FallbackActivityMarker` (the built-in "404 Activity").

```php
#[IntentFilter(action: 'RELAY_TO_SPECIALIST', pattern: '/^\/ask /')]
final class RelayActivity extends BotActivity { /* ... */ }
```

## Back Stack

`Kernel\BackStackStore` keeps each user's stack (keyed by `chat_id`)
in cache (Redis or filesystem). Every time an Activity finishes
running successfully (and the navigation chain completes), a new
`Activity\BackStackEntry` is pushed — unless the Intent has the
`NoHistory` flag.

Activities can indirectly request to go back:

```php
return $this->finishWithResult(['choice' => 'confirmed']);
```

## Deep Links — Direct Entry with a /start Parameter

`Kernel\DeepLinkResolver` converts a `/start <payload>` parameter
(like `t.me/YourBot?start=order:id=42`) into an explicit Intent:

```php
$resolver = new DeepLinkResolver();
$resolver->registerRoute('order', OrderActivity::class);

$payload = DeepLinkResolver::extractStartPayload($update->text); // "order:id=42"
$intent = $resolver->resolve($payload); // Intent::to(OrderActivity::class)->putExtra('id', '42')
```

The payload format is `route:key1=val1,key2=val2`.
