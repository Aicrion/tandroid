# Testing

The framework is tested with PHPUnit 11 and Mockery. `phpunit.xml`
defines two suites: `Unit` (`tests/Unit`) and `Feature`
(`tests/Feature`).

```bash
vendor/bin/phpunit
```

## Test Folder Structure

```
tests/
├── Unit/
│   ├── ActivityManagerChainTest.php   # NavigationRequest navigation chain
│   ├── IntentTest.php                 # Intent object construction/immutability
│   ├── TranslatorTest.php             # translation with parameters and fallback
│   └── ViewModelTest.php              # ViewModel hydrate/dehydrate
├── Feature/
│   └── BuildSmokeTest.php             # overall project health (critical files, manifests)
└── Fixtures/
    ├── StubContainer.php              # minimal PSR-11 container for tests
    ├── ChainStartActivity.php         # fixture Activity that navigates immediately
    └── ChainTargetActivity.php        # fixture destination Activity
```

## Testing a Single Activity

Since every `BotActivity` is a plain PHP class (no hidden dependency
on superglobals), unit-testing one is straightforward: instantiate
it, call `bindUpdate()`, run `onCreate()` with a custom `Intent`, and
assert on the `getContentView()` output:

```php
use Aicrion\Tandroid\Intent\Intent;
use Aicrion\Tandroid\Update\{Update, UpdateType};
use Greeter\ProfileActivity;

final class ProfileActivityTest extends TestCase
{
    public function test_it_shows_the_user_id(): void
    {
        $activity = new ProfileActivity();
        $activity->bindUpdate(new Update(updateId: 1, chatId: 1, userId: 99, type: UpdateType::Message));

        $activity->onCreate(Intent::action('VIEW_PROFILE'));

        $this->assertStringContainsString('99', $activity->getContentView()?->text);
    }
}
```

## Testing a Navigation Chain with ActivityManager

For higher-level tests that actually involve
`Kernel\ActivityManager` (e.g. to verify a `NavigationRequest` is
actually followed), use `Tests\Fixtures\StubContainer` — a minimal
PSR-11 container that directly instantiates any zero-argument class
— and `Symfony\Component\Cache\Adapter\ArrayAdapter` as an in-memory
cache:

```php
$manager = new ActivityManager(
    container: new StubContainer(),
    intentResolver: new IntentResolver(registry: []),
    backStack: new BackStackStore(new ArrayAdapter()),
    stateStore: new StateStore(new ArrayAdapter()),
);

$update = new Update(
    updateId: 1,
    chatId: 555,
    userId: 42,
    type: UpdateType::CallbackQuery,
    callbackData: json_encode(['a' => ChainStartActivity::class, 'p' => []]),
);

$view = $manager->dispatch($update);

$this->assertSame('landed:chain-start', $view->text);
```

This is exactly the pattern implemented in
`tests/Unit/ActivityManagerChainTest.php` — see the full example
there.

## Testing Repositories / Doctrine-Dependent Code

Since `EntityManagerInterface` is a regular dependency, mock it with
Mockery for unit tests; for integration tests that actually need a
database, build an in-memory SQLite connection:

```php
$config = ORMSetup::createAttributeMetadataConfiguration([__DIR__ . '/../src/Entity'], isDevMode: true);
$connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
$entityManager = new EntityManager($connection, $config);

$tool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
$tool->createSchema($entityManager->getMetadataFactory()->getAllMetadata());
```

## Don't Hit Real Telegram Outbound Calls

Never make real HTTP requests to `api.telegram.org` in unit tests.
Every `Api\*Request` class accepts a
`Symfony\Contracts\HttpClient\HttpClientInterface` in its
constructor — use
`Symfony\Component\HttpClient\MockHttpClient`:

```php
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

$client = new MockHttpClient([new MockResponse(json_encode(['ok' => true, 'result' => []]))]);
$request = new SendMessageRequest($client, 'fake-token');
$request->to(1)->text('Hello')->send();
```

## Guidelines for Writing New Tests

- Put fixture classes (like helper Activities) in `tests/Fixtures/`,
  not `tests/Unit/`, so PHPUnit doesn't mistakenly try to run them
  as tests.
- For every core architecture change (like modifying
  `ActivityManager`), always add an integration-level test like
  `ActivityManagerChainTest` — a regression there means the entire
  framework's navigation mechanism is broken.