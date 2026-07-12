<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Tests\Unit;

use Aicrion\Tandroid\Kernel\ActivityManager;
use Aicrion\Tandroid\Kernel\BackStackStore;
use Aicrion\Tandroid\Kernel\IntentResolver;
use Aicrion\Tandroid\Kernel\ViewModel\StateStore;
use Aicrion\Tandroid\Tests\Fixtures\ChainStartActivity;
use Aicrion\Tandroid\Tests\Fixtures\StubContainer;
use Aicrion\Tandroid\Update\Update;
use Aicrion\Tandroid\Update\UpdateType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Guards the framework's central promise: a NavigationRequest
 * returned from a lifecycle hook must be followed transparently,
 * inside the very same incoming Update, without another round-trip
 * to Telegram.
 */
final class ActivityManagerChainTest extends TestCase
{
    public function test_it_follows_a_navigation_chain_within_a_single_update(): void
    {
        $container = new StubContainer();
        $cache = new ArrayAdapter();

        $manager = new ActivityManager(
            container: $container,
            intentResolver: new IntentResolver(registry: []),
            backStack: new BackStackStore($cache),
            stateStore: new StateStore($cache),
        );

        $update = new Update(
            updateId: 1,
            chatId: 555,
            userId: 42,
            type: UpdateType::CallbackQuery,
            callbackData: json_encode(['a' => ChainStartActivity::class, 'p' => []], JSON_THROW_ON_ERROR),
        );

        $view = $manager->dispatch($update);

        $this->assertNotNull($view);
        $this->assertSame('landed:chain-start', $view->text);
    }
}