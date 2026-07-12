# ViewModel and State Management

Because every `BotActivity` is **freshly instantiated on every
request** (PHP holds no memory between requests), regular class
properties never survive between two user taps. The framework's
solution is exactly what Android calls
`androidx.lifecycle.ViewModel`: a state holder that outlives the
Activity itself.

## Creating a ViewModel

```php
use Aicrion\Tandroid\Kernel\ViewModel\ViewModel;

final class CartViewModel extends ViewModel
{
    public function addItem(string $sku): void
    {
        $items = $this->get('items', []);
        $items[] = $sku;
        $this->set('items', $items);
    }

    public function items(): array
    {
        return $this->get('items', []);
    }
}
```

The base `ViewModel` exposes just two protected methods to
subclasses: `get(string $key, mixed $default = null)` and
`set(string $key, mixed $value)`. All state is kept in an internal
array that gets serialized/deserialized via `hydrate()`/
`dehydrate()` (called only by `StateStore`, never manually).

## Using It in an Activity

By combining `HasViewModel` onto any `BotActivity`, the protected
`viewModel()` method becomes available:

```php
use Aicrion\Tandroid\Activity\{BotActivity, HasViewModel, NavigationRequest};
use Aicrion\Tandroid\Intent\Intent;
use Aicrion\Tandroid\View\View;

final class CartActivity extends BotActivity
{
    use HasViewModel;

    public function onCreate(Intent $intent): ?NavigationRequest
    {
        $cart = $this->viewModel(CartViewModel::class);
        $cart->addItem($intent->getExtra('sku'));

        $this->setContentView(View::message('Cart items: ' . count($cart->items())));

        return null;
    }
}
```

That's it. You don't need to persist anything yourself —
`ActivityManager` automatically calls `persistViewModel()` on any
Activity using `HasViewModel` right after every lifecycle hook
completes.

## How It Works Under the Hood

1. `Kernel::boot()` builds an instance of
   `Kernel\ViewModel\StateStore` and injects it into
   `ActivityManager`.
2. For every Activity instance, `ActivityManager` checks
   `method_exists($activity, 'bindStateStore')`; if true (meaning
   `HasViewModel` is used), it injects `StateStore`.
3. The first call to `$this->viewModel(FooViewModel::class)` inside
   your Activity calls `StateStore::resolve()`, which creates a fresh
   `FooViewModel` instance and — if previous state exists in cache —
   restores it via `hydrate()`.
4. After `onCreate`/`onNewIntent`/`onResume` finishes,
   `ActivityManager` calls `persistViewModel()` on that same
   Activity, saving the new state back into `StateStore` (only if
   `viewModel()` was actually called).

## The Scope of a ViewModel

The storage key is built from a combination of the user's `chat_id`
and the ViewModel class's FQCN:

```
aicrion.viewmodel.{ViewModel_FQCN_underscored}.{chat_id}
```

This means a `CartViewModel` shared across multiple Activities (e.g.
`ProductActivity` → `CartActivity` → `CheckoutActivity`) all running
for the same user **see the same shared state** — exactly like a
ViewModel surviving Android recreating the Activity on a screen
rotation.

To explicitly clear a ViewModel from cache (e.g. after an order
completes):

```php
$this->stateStore->clear(CartViewModel::class, $this->update->chatId);
```

(Since `HasViewModel` defines this property as `private` within your
own class, it's directly accessible from your Activity's methods —
no separate getter needed.)

## When to Use a ViewModel vs. an Extra

| Need | Right tool |
|---|---|
| A small value that only needs to reach the next Activity | `Intent::putExtra()` |
| State that must survive across multiple Activities/user taps | `ViewModel` |
| The final result of a form/wizard before reaching the confirmation step | Usually `ViewModel` (since `FormWidget`/`WizardWidget` are themselves stateless) |
