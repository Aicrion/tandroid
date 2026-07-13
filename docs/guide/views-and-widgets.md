# Views and Widgets

`View\View` is the equivalent of Android's `View`/`ViewGroup`, and
`Widget\WidgetInterface` is the equivalent of Compose/XML UI
components. Every Activity builds a `View` in `onCreate()`/
`onResume()` and declares it as the rendered content with
`$this->setContentView($view)`.

## Building a View

```php
use Aicrion\Tandroid\View\View;
use Aicrion\Tandroid\View\ParseMode;

$view = View::message('Hello! 👋', ParseMode::HTML);
```

`View` is **immutable** — every method returns a new instance
(similar to immutable state in Jetpack Compose):

```php
public function attach(WidgetInterface $widget): self; // adds a widget (keyboard, table, etc.)
public function withKeyboard(WidgetInterface $keyboard): self; // alias of attach(), for readability
public function render(): array; // becomes the final sendMessage payload
```

The final output of `render()` is exactly what
`Kernel::handle()` automatically sends to the Telegram API:

```php
['text' => '...', 'parse_mode' => 'MarkdownV2', 'reply_markup' => [...]]
```

## Deleting the Previous Message (Optional)

By default, every reply Kernel sends is a **new** Telegram message —
nothing already in the chat is ever touched (the one exception is
`IntentFlag::ReplaceMessage`, covered below, which edits a message
in place instead of sending a new one). That means an Activity
reachable via `#[IntentFilter(action: 'MAIN')]` — e.g. `StartActivity`
answering `/start` — sends a brand-new message every single time the
user re-triggers it, and every older one is left behind in the chat.

If you'd rather the previous bot message disappear first, call
`deletePreviousMessage()` on the `View` you return:

```php
$this->setContentView(
    View::message('Welcome back! 👋')
        ->attach($keyboard)
        ->deletePreviousMessage(),
);
```

This is **opt-in per View** — nothing changes for any Activity that
doesn't call it. When set, `Kernel` looks up the message_id it
recorded the last time it sent *anything* to this chat and calls
Telegram's `deleteMessage` on it right before sending the new one.

A few things worth knowing before you turn it on:

- **It's best-effort, by design.** If there's no previous message on
  record for the chat, or Telegram refuses the delete (the message is
  already gone, was sent by someone else, or is older than Telegram's
  ~48h delete window), the delete is silently skipped and your View
  is still sent normally — this never blocks or fails the reply.
- **It deletes the bot's *own* last message, not "the message the
  user tapped".** For that in-place-edit use case (e.g. a Wizard
  page, or a checkbox toggle) you almost always want
  `Button::actionReplace()` / `IntentFlag::ReplaceMessage` instead,
  which edits the tapped message rather than deleting-then-resending
  — cheaper, and doesn't cause a visible flicker/reorder in the chat.
  Reach for `deletePreviousMessage()` specifically for the case
  `ReplaceMessage` can't cover: an Activity re-entered from a plain
  text command (`/start`, a Reply keyboard button, ...), which is
  never a `callback_query` and therefore has no message to edit.
- **It's chat-wide, not Activity-specific.** The "previous message"
  is whatever the bot sent last to that chat, regardless of which
  Activity produced it — deleting it doesn't know or care whether it
  was, say, the same `StartActivity` screen or something else
  entirely the user navigated through in between.
- Don't combine it with messages you want to stay in the chat history
  on purpose (confirmations, receipts, anything the user might want
  to scroll back to) — it is meant for "redraw the current screen",
  not general-purpose cleanup.

## WidgetInterface

Every widget has a single contract:

```php
interface WidgetInterface
{
    /** @return array<string, mixed> a fragment of the final sendMessage payload */
    public function render(): array;
}
```

## Buttons and Keyboards (Button / Keyboard)

`Widget\Button` builds three kinds of buttons:

```php
use Aicrion\Tandroid\Widget\Button;

Button::action('My Profile', to: ProfileActivity::class);              // navigate to another Activity
Button::action('Delete Order', to: OrderActivity::class, payload: ['id' => 42]); // with an extra
Button::url('Docs', 'https://example.com/docs');                        // external link
Button::requestContact('Share phone number');                           // request contact
```

An `action` button automatically encodes `to` and `payload` into
`callback_data` as JSON (`{"a": "...", "p": {...}}`) — that exact
structure is what `Kernel\IntentResolver` on the receiving side
converts into an explicit Intent (see
[Activities and Intents](activities-and-intents.md)).

`Widget\Keyboard` arranges buttons row by row — exactly like a
vertical `LinearLayout` where each row is itself a horizontal
`LinearLayout`:

```php
use Aicrion\Tandroid\Widget\Keyboard;

$keyboard = Keyboard::inline()
    ->row(Button::action('My Profile', to: ProfileActivity::class))
    ->row(Button::url('Docs', 'https://example.com/docs'));

$view = View::message('Welcome')->attach($keyboard);
```

For a Reply keyboard (not Inline):

```php
Keyboard::reply()->row(Button::requestContact());
// or directly:
Keyboard::requestContact('Send my number');
```

`Keyboard::reply()` always sends `resize_keyboard: true` to Telegram
by default, so buttons shrink to fit their labels instead of using
Telegram's oversized default keys. Pass `false` if you want the
large default size instead:

```php
Keyboard::reply(resizeKeyboard: false)->row(Button::action('Menu', to: MenuActivity::class));
// or, on an already-built keyboard:
Keyboard::reply()->resizeKeyboard(false)->row(/* ... */);
```

**`Button::action()`/`Button::actionReplace()` also work inside
`Keyboard::reply()`**, navigating to another Activity exactly like
they do on an Inline keyboard — but the mechanism is different, and
worth understanding:

- On an **Inline** keyboard, a tap comes back as a `callback_query`
  carrying that exact button's `callback_data`, which encodes the
  target Activity directly (see `CallbackDataStore`).
- Telegram gives Reply buttons no such thing — a tap just sends a
  normal text message whose content is the button's own label, as if
  the user had typed it. So the framework keeps track, per chat, of
  which label on the *currently visible* Reply keyboard maps to which
  Activity (`Kernel\ReplyActionStore`), and `IntentResolver` checks
  that mapping before falling back to `#[IntentFilter]` matching.

This has two practical consequences:

1. It's chat-scoped and always reflects the **last** Reply keyboard
   sent to that chat — as soon as you send a different Reply
   keyboard (or none at all), the old labels stop resolving. Don't
   rely on a Reply button staying "valid" across an unrelated
   Activity in between.
2. Because a tap is a plain Message, not a `callback_query`,
   `IntentFlag::ReplaceMessage` (from `Button::actionReplace()`) has
   nothing to edit in place — there's no previous message id to
   target — so it silently behaves like `Button::action()` on a
   Reply keyboard. Prefer plain `Button::action()` there to avoid
   the confusion.

```php
$keyboard = Keyboard::reply()
    ->row(Button::action('👤 My Profile', to: ProfileActivity::class));

$view = View::message('Welcome!')->attach($keyboard);
```

## Multi-Select Checkboxes (CheckboxGroupWidget)

The equivalent of a group of `CheckBox`es in Android — each option is
a toggleable inline button, and the selection state is encoded
directly in `callback_data` (no server-side state needed between
taps):

```php
use Aicrion\Tandroid\Widget\CheckboxGroupWidget;

$group = CheckboxGroupWidget::make('interests')
    ->option('sport', 'Sports')
    ->option('tech', 'Technology')
    ->checkedValues(['tech']); // pre-selected options

$view = View::message('Select your interests:')->attach($group);
```

Every tap on an option sends a `callback_data` shaped like
`{"w":"checkbox","n":"interests","v":"sport","c":true}` —
your Activity is responsible for reading `n`/`v`/`c` from
`update->callbackData` (or via `Intent`) and updating the actual
state (usually in a [ViewModel](viewmodel-and-state.md)).

## Multi-Step Form (FormWidget)

For linear, text-based forms (e.g. "Name?" → "Age?" → "Confirm"):

```php
use Aicrion\Tandroid\Widget\FormWidget;

$form = FormWidget::make()
    ->step('name', 'Enter your name:')
    ->step('age', 'Enter your age:', validator: static fn (string $v) => is_numeric($v));

// The Activity, after receiving the user's reply:
$result = $form->withAnswers($savedAnswers, $savedStep)->submit($update->text);

match (true) {
    $result->isInvalid  => /* re-show the same step with an error message: $result->error / $result->nextPrompt */,
    $result->isComplete => /* $result->answers holds the final data */,
    default              => /* next step: $result->nextPrompt, step: $result->nextStep */,
};
```

`FormSubmissionResult` has three states: `next()`, `complete()`,
`invalid()` — with corresponding readonly properties for checking
(`isComplete`, `isInvalid`). Persisting `$savedAnswers`/`$savedStep`
between requests is your responsibility; usually kept in a
ViewModel.

## Multi-Page Wizard (WizardWidget)

For flows that need explicit "Previous/Next" buttons and pages with
custom widgets (not just text):

```php
use Aicrion\Tandroid\Widget\{WizardWidget, WizardPage, Button};

$wizard = WizardWidget::make(ownerActivity: SettingsActivity::class)
    ->page(new WizardPage('Choose your language', buttons: [[Button::action('English', to: SettingsActivity::class)]]))
    ->page(new WizardPage('Choose your timezone'))
    ->atIndex($currentIndex);

$view = View::message($wizard->currentPage()?->text ?? '')->attach($wizard);
```

The "Previous/Next" buttons automatically point back to the same
`ownerActivity` with a `wizard_step` extra; the Activity must read
this extra in `onNewIntent()`/`onCreate()` to set `atIndex()`
correctly.

## Rich Messages (View\Rich\*)

For content that goes beyond plain text, the `View\Rich` namespace
provides composable blocks, all producing Markdown/HTML output
compatible with Telegram:

| Class | Purpose |
|---|---|
| `Rich\TableBlock` | Monospace text table |
| `Rich\CodeBlock` | Code block with a language hint |
| `Rich\QuoteBlock` | Blockquote |
| `Rich\ListBlock` | Ordered/unordered list |
| `Rich\MapBlock` | Map link (static location) |
| `Rich\SlideshowBlock` | A set of images as a slideshow (media group) |
| `Rich\RichMessage` | Composes several blocks into a single message |

```php
use Aicrion\Tandroid\View\Rich\{RichMessage, TableBlock, CodeBlock};

$message = RichMessage::make()
    ->block((new TableBlock(['Name', 'Price']))->row('Book', '$5.00')->row('Notebook', '$1.50'))
    ->block(new CodeBlock('echo "hello";', language: 'php'));
```

## StreamingView — Incremental Replies (like edit_message)

`View\StreamingView` is designed for replies that need to be
completed gradually (e.g. output from a language model arriving
token by token): each call to `push()` accumulates text, and
`render()` ultimately produces the same standard structure as
`View::render()`, letting an Activity switch between the initial send
and subsequent edits.

## Next Step

Wherever widgets or forms need to keep state across multiple user
taps (like `$savedAnswers`/`$currentStep` above), read
[ViewModel and State Management](viewmodel-and-state.md).