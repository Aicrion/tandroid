<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Tests\Unit;

use Aicrion\Tandroid\Intent\Intent;
use Aicrion\Tandroid\Kernel\CallbackDataStore;
use Aicrion\Tandroid\Kernel\IntentResolver;
use Aicrion\Tandroid\Update\Update;
use Aicrion\Tandroid\Update\UpdateType;
use Aicrion\Tandroid\Widget\Button;
use Aicrion\Tandroid\Widget\Keyboard;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Regression coverage for the fix that keeps callback_data under
 * Telegram's hard 64-byte limit regardless of how long the target
 * Activity's FQCN is (see CallbackDataStore's docblock). Without
 * this, a button targeting an Activity in a namespace as short as
 * "App\Plugins\Greeter" already produces a callback_data payload
 * over 64 bytes once the IntentFlag::ReplaceMessage flag is added —
 * exactly the shape used in the tutorial's own example — and
 * Telegram rejects the entire sendMessage call with a 400.
 */
final class CallbackDataStoreTest extends TestCase
{
    /** A namespace this deep alone would previously blow well past 64 bytes of callback_data. */
    private const string LONG_FQCN = 'App\\Plugins\\Greeter\\Onboarding\\Steps\\ProfileConfirmationActivity';

    protected function tearDown(): void
    {
        // Each test that touches the static facade re-configures it
        // explicitly, but reset defensively so test order can never matter.
        CallbackDataStore::configure(new ArrayAdapter());
    }

    public function test_put_and_get_round_trip_a_payload(): void
    {
        $store = new CallbackDataStore(new ArrayAdapter());
        $payload = ['a' => self::LONG_FQCN, 'p' => ['id' => 42], 'f' => ['ReplaceMessage']];

        $token = $store->put($payload);

        $this->assertSame($payload, $store->get($token));
    }

    public function test_unknown_token_resolves_to_null(): void
    {
        $store = new CallbackDataStore(new ArrayAdapter());

        $this->assertNull($store->get('not-a-real-token'));
    }

    public function test_button_with_long_fqcn_and_replace_flag_stays_under_telegram_limit(): void
    {
        CallbackDataStore::configure(new ArrayAdapter());

        $button = Button::actionReplace('⬅️ Back', to: self::LONG_FQCN);
        $keyboard = Keyboard::inline()->row($button);

        $callbackData = $keyboard->render()['reply_markup']['inline_keyboard'][0][0]['callback_data'];

        $this->assertLessThanOrEqual(64, strlen($callbackData));
    }

    public function test_identical_buttons_reuse_the_same_token(): void
    {
        CallbackDataStore::configure(new ArrayAdapter());

        $first = Button::actionReplace('⬅️ Back', to: self::LONG_FQCN)->resolveCallbackData();
        $second = Button::actionReplace('⬅️ Back', to: self::LONG_FQCN)->resolveCallbackData();

        $this->assertSame($first, $second);
    }

    public function test_intent_resolver_recovers_the_original_intent_from_a_token(): void
    {
        $cache = new ArrayAdapter();
        CallbackDataStore::configure($cache);

        $button = Button::actionReplace('⬅️ Back', to: self::LONG_FQCN, payload: ['id' => 7]);
        $token = $button->resolveCallbackData();

        $resolver = new IntentResolver(registry: [], callbackDataStore: new CallbackDataStore($cache));
        $update = new Update(
            updateId: 1,
            chatId: 555,
            userId: 42,
            type: UpdateType::CallbackQuery,
            callbackData: $token,
        );

        $intent = $resolver->resolve($update);

        $this->assertSame(self::LONG_FQCN, $intent->activityClass);
        $this->assertSame(7, $intent->getExtra('id'));
    }

    public function test_legacy_raw_json_callback_data_still_resolves(): void
    {
        $resolver = new IntentResolver(registry: []);

        $update = new Update(
            updateId: 1,
            chatId: 555,
            userId: 42,
            type: UpdateType::CallbackQuery,
            callbackData: json_encode(['a' => Intent::class, 'p' => []], JSON_THROW_ON_ERROR),
        );

        $intent = $resolver->resolve($update);

        $this->assertSame(Intent::class, $intent->activityClass);
    }

    public function test_button_without_a_configured_store_falls_back_to_raw_json(): void
    {
        // Simulates a Button built without ever booting the Kernel.
        $ref = new \ReflectionClass(CallbackDataStore::class);
        $property = $ref->getProperty('facade');
        $property->setAccessible(true);
        $property->setValue(null, null);

        $callbackData = Button::action('Back', to: self::LONG_FQCN)->resolveCallbackData();

        $this->assertJson($callbackData);
        $this->assertSame(self::LONG_FQCN, json_decode($callbackData, true)['a']);
    }
}