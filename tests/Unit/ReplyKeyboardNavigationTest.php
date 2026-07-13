<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Tests\Unit;

use Aicrion\Tandroid\Intent\IntentFlag;
use Aicrion\Tandroid\Kernel\IntentResolver;
use Aicrion\Tandroid\Kernel\ReplyActionStore;
use Aicrion\Tandroid\Update\Update;
use Aicrion\Tandroid\Update\UpdateType;
use Aicrion\Tandroid\Widget\Button;
use Aicrion\Tandroid\Widget\Keyboard;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Regression coverage for making `Button::action()`/`actionReplace()`
 * work inside `Keyboard::reply()`, not just `Keyboard::inline()`.
 *
 * Before this fix, `Keyboard::render()` put `callback_data` on every
 * button regardless of keyboard type — invalid for a Reply keyboard,
 * since Telegram only sends the button's own label text back on tap.
 * Tapping such a button therefore never reached the target Activity
 * (see `ReplyActionStore`'s docblock for the full mechanism this
 * test exercises).
 */
final class ReplyKeyboardNavigationTest extends TestCase
{
    public function test_reply_keyboard_action_button_carries_no_callback_data_or_url(): void
    {
        $rendered = Keyboard::reply()
            ->row(Button::action('👤 My Profile', to: 'App\\Plugins\\Greeter\\ProfileActivity'))
            ->render();

        $button = $rendered['reply_markup']['keyboard'][0][0];

        $this->assertSame('👤 My Profile', $button['text']);
        $this->assertArrayNotHasKey('callback_data', $button);
        $this->assertArrayNotHasKey('url', $button);
    }

    public function test_reply_keyboard_resizes_by_default(): void
    {
        $rendered = Keyboard::reply()->row(Button::action('Menu', to: 'X'))->render();

        $this->assertTrue($rendered['reply_markup']['resize_keyboard']);
    }

    public function test_reply_keyboard_resize_can_be_disabled_at_construction(): void
    {
        $rendered = Keyboard::reply(resizeKeyboard: false)->row(Button::action('Menu', to: 'X'))->render();

        $this->assertFalse($rendered['reply_markup']['resize_keyboard']);
    }

    public function test_reply_keyboard_resize_can_be_disabled_fluently(): void
    {
        $rendered = Keyboard::reply()->resizeKeyboard(false)->row(Button::action('Menu', to: 'X'))->render();

        $this->assertFalse($rendered['reply_markup']['resize_keyboard']);
    }

    public function test_inline_keyboard_is_unaffected_by_the_reply_keyboard_changes(): void
    {
        $rendered = Keyboard::inline()->row(Button::action('Profile', to: 'X'))->render();

        $this->assertArrayHasKey('callback_data', $rendered['reply_markup']['inline_keyboard'][0][0]);
        $this->assertArrayNotHasKey('resize_keyboard', $rendered['reply_markup']);
        $this->assertArrayNotHasKey('reply_actions', $rendered);
    }

    public function test_request_contact_reply_button_still_works_with_no_stray_callback_data(): void
    {
        $rendered = Keyboard::requestContact('Share number')->render();
        $button = $rendered['reply_markup']['keyboard'][0][0];

        $this->assertTrue($button['request_contact']);
        $this->assertArrayNotHasKey('callback_data', $button);
        $this->assertSame([], $rendered['reply_actions']);
    }

    public function test_reply_action_store_round_trips_and_is_chat_scoped(): void
    {
        $store = new ReplyActionStore(new ArrayAdapter());
        $store->remember(555, ['👤 My Profile' => ['a' => 'ProfileActivity', 'p' => [], 'f' => []]]);

        $this->assertSame('ProfileActivity', $store->get(555, '👤 My Profile')['a']);
        $this->assertNull($store->get(555, 'not a real label'));
        $this->assertNull($store->get(999, '👤 My Profile'), 'mapping must not leak across chats');
    }

    public function test_reply_action_store_forgets_stale_labels_once_a_new_keyboard_is_remembered(): void
    {
        $store = new ReplyActionStore(new ArrayAdapter());
        $store->remember(555, ['Old Label' => ['a' => 'OldActivity', 'p' => [], 'f' => []]]);
        $store->remember(555, []); // a different keyboard replaced the old one, with no action buttons

        $this->assertNull($store->get(555, 'Old Label'));
    }

    public function test_intent_resolver_routes_a_reply_keyboard_tap_to_the_explicit_activity(): void
    {
        $store = new ReplyActionStore(new ArrayAdapter());
        $store->remember(777, [
            '👤 My Profile' => ['a' => 'App\\Plugins\\Greeter\\ProfileActivity', 'p' => ['x' => 1], 'f' => ['ReplaceMessage']],
        ]);

        $resolver = new IntentResolver(registry: [], replyActionStore: $store);
        $update = new Update(updateId: 1, chatId: 777, userId: 42, type: UpdateType::Message, text: '👤 My Profile');

        $intent = $resolver->resolve($update);

        $this->assertSame('App\\Plugins\\Greeter\\ProfileActivity', $intent->activityClass);
        $this->assertSame(1, $intent->getExtra('x'));
        $this->assertTrue($intent->hasFlag(IntentFlag::ReplaceMessage));
    }

    public function test_intent_resolver_falls_back_to_implicit_resolution_when_text_matches_no_reply_action(): void
    {
        $store = new ReplyActionStore(new ArrayAdapter());
        $store->remember(777, ['👤 My Profile' => ['a' => 'ProfileActivity', 'p' => [], 'f' => []]]);

        $resolver = new IntentResolver(registry: [], replyActionStore: $store);
        $update = new Update(updateId: 2, chatId: 777, userId: 42, type: UpdateType::Message, text: '/start');

        $intent = $resolver->resolve($update);

        $this->assertFalse($intent->isExplicit());
    }

    public function test_callback_query_path_still_takes_priority_over_reply_action_lookup(): void
    {
        $store = new ReplyActionStore(new ArrayAdapter());
        $resolver = new IntentResolver(registry: [], replyActionStore: $store);

        $update = new Update(
            updateId: 3,
            chatId: 1,
            userId: 1,
            type: UpdateType::CallbackQuery,
            callbackData: json_encode(['a' => 'SomeActivity', 'p' => ['id' => 9]], JSON_THROW_ON_ERROR),
        );

        $intent = $resolver->resolve($update);

        $this->assertSame('SomeActivity', $intent->activityClass);
        $this->assertSame(9, $intent->getExtra('id'));
    }
}
