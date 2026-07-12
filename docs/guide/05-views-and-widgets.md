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
[Activities and Intents](04-activities-and-intents.md)).

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
state (usually in a [ViewModel](06-viewmodel-and-state.md)).

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
[ViewModel and State Management](06-viewmodel-and-state.md).
