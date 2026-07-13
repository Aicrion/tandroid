<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Tests\Unit;

use Aicrion\Tandroid\Kernel\LastMessageStore;
use Aicrion\Tandroid\View\View;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Covers the optional View::deletePreviousMessage() feature: the
 * View-level opt-in flag itself, and the LastMessageStore that
 * Kernel::deliver() uses to know what to delete. Kernel::deliver()'s
 * actual Telegram calls aren't covered here (it talks to the
 * Telegram API through a static facade with no seam for a fake
 * HTTP client in a plain unit test) — see this file's tests plus
 * ReplyKeyboardNavigationTest for the parts that are unit-testable
 * in isolation.
 */
final class DeletePreviousMessageTest extends TestCase
{
    public function test_delete_previous_message_defaults_to_false(): void
    {
        $view = View::message('hi');

        $this->assertFalse($view->render()['delete_previous_message']);
    }

    public function test_delete_previous_message_can_be_opted_into(): void
    {
        $view = View::message('hi')->deletePreviousMessage();

        $this->assertTrue($view->render()['delete_previous_message']);
    }

    public function test_view_is_immutable_when_opting_in(): void
    {
        $original = View::message('hi');
        $original->deletePreviousMessage();

        $this->assertFalse($original->render()['delete_previous_message']);
    }

    public function test_delete_previous_message_can_be_explicitly_turned_off(): void
    {
        $view = View::message('hi')->deletePreviousMessage()->deletePreviousMessage(false);

        $this->assertFalse($view->render()['delete_previous_message']);
    }

    public function test_last_message_store_round_trips_and_overwrites(): void
    {
        $store = new LastMessageStore(new ArrayAdapter());

        $this->assertNull($store->get(42));

        $store->remember(42, 1001);
        $this->assertSame(1001, $store->get(42));

        $store->remember(42, 1002);
        $this->assertSame(1002, $store->get(42), 'a newer message must overwrite the older one');
    }

    public function test_last_message_store_is_chat_scoped(): void
    {
        $store = new LastMessageStore(new ArrayAdapter());
        $store->remember(1, 100);

        $this->assertNull($store->get(2));
    }

    public function test_last_message_store_forgets_on_request(): void
    {
        $store = new LastMessageStore(new ArrayAdapter());
        $store->remember(42, 1001);
        $store->forget(42);

        $this->assertNull($store->get(42));
    }
}
