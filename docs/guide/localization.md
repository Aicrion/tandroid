# Localization (i18n)

Unlike most PHP translation libraries, `I18n\Translator` reads
language files as **PHP arrays** (not flat JSON/YAML) — meaning each
entry can be an actual **Closure**, and therefore supports any
arbitrary expression (pluralization, gender matching, computed
formatting), not just simple placeholder substitution.

## Language Folder Structure

```
lang/
├── en/
│   └── order.php
└── fa/
    └── order.php
```

Each file is a logical namespace (e.g. `order`), and the final
translation key is read as `{file}.{key}` (e.g. `order.created`).

## Writing a Language File

```php
// lang/en/order.php
return [
    // A simple string with :name-style placeholders
    'greeting' => 'Hello :name, welcome!',

    // A Closure with custom parameters — real pluralization, not just singular/plural
    'created' => fn (int $count) => match (true) {
        $count === 1 => 'One order was placed successfully ✅',
        $count > 1 => "{$count} orders were placed successfully ✅",
        default => 'No orders were placed',
    },

    // A Closure for computed formatting
    'total_price' => fn (int $amount) => sprintf('Total: $%s', number_format($amount)),
];
```

## Usage

```php
use Aicrion\Tandroid\I18n\Translator;

$translator = Translator::create(langPath: __DIR__ . '/lang', defaultLocale: 'en');

$translator->trans('order.greeting', ['name' => 'Alex']);
// => "Hello Alex, welcome!"

$translator->trans('order.created', [3]);
// => "3 orders were placed successfully ✅"   (Closure parameters are passed positionally)

$translator->trans('order.created', [3], locale: 'fa');
// => "3 سفارش با موفقیت ثبت شد ✅"
```

### Key Resolution Rules

1. If an entry is a **Closure**, the `trans()` params array is spread
   (`...$params`) into it — so parameter order must match the
   Closure's signature.
2. If an entry is a **string**, every `:key` in it is replaced with
   `$params['key']` (`interpolate()`).
3. If the key isn't found in the requested locale, the Translator
   automatically falls back to its constructor's `defaultLocale`.
4. If the key isn't found in any locale, the key itself
   (`order.created`) is returned — not an exception — so a missing
   translation doesn't break the whole request.

## Using It in an Activity

Since `Translator` is a regular class, type-hint it in your
Activity's constructor (or register a shared instance yourself as a
service in the DI Container):

```php
final class OrderActivity extends BotActivity
{
    public function __construct(
        private readonly Translator $translator,
    ) {}

    public function onCreate(Intent $intent): ?NavigationRequest
    {
        $locale = $this->update->raw['message']['from']['language_code'] ?? 'en';

        $this->setContentView(
            View::message($this->translator->trans('order.created', [1], $locale)),
        );

        return null;
    }
}
```

> Note: `Translator` isn't automatically registered in the DI
> Container by default (because the `lang/` path is host-project
> specific, not something the framework itself owns). In your own
> host project, add
> `$container->set(Translator::class, Translator::create(...))`, or
> for simplicity, build a singleton inside your own wrapper service.
