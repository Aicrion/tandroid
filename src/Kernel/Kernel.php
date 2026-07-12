<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Kernel;

use Aicrion\Tandroid\Api\Telegram;
use Aicrion\Tandroid\Broadcast\BroadcastDispatcher;
use Aicrion\Tandroid\Broadcast\Event\UserJoinedEvent;
use Aicrion\Tandroid\Cache\CachePoolFactory;
use Aicrion\Tandroid\Config\FrameworkConfig;
use Aicrion\Tandroid\Database\EntityManagerFactory;
use Aicrion\Tandroid\Intent\IntentFlag;
use Aicrion\Tandroid\Kernel\ViewModel\StateStore;
use Aicrion\Tandroid\Package\PackageManager;
use Aicrion\Tandroid\Update\Update;
use Aicrion\Tandroid\View\View;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Boot entry point of the whole framework — the equivalent of the
 * Android Zygote + System Server combined. Responsible for:
 *
 *   1. Loading FrameworkConfig (config/aicrion.yaml + env overrides)
 *   2. Building the Symfony DI container
 *   3. Asking PackageManager to scan every installed plugin, register
 *      its Activities/Entities, and run pending Doctrine migrations
 *      automatically — with zero CLI interaction, so it also works
 *      on shared hosting.
 *   4. Wiring the ActivityManager, IntentResolver, BackStackStore and
 *      StateStore, configuring the outbound Telegram HTTP client, and
 *      registering every plugin's BroadcastReceivers.
 *
 * A single call to boot() is all a host application (webhook.php or
 * a polling daemon) ever needs. handle() drives one Update all the
 * way through Activity dispatch AND delivers the resulting View back
 * to the user — callers don't need to talk to the Telegram API
 * themselves for the common case.
 */
final class Kernel
{
    private ?ContainerBuilder $container = null;

    private ?ActivityManager $activityManager = null;

    private ?HttpClientInterface $httpClient = null;

    public function __construct(
        private readonly FrameworkConfig $config,
    ) {}

    public static function fromConfigFile(string $path): self
    {
        return new self(FrameworkConfig::fromFile($path));
    }

    public function boot(): self
    {
        $container = new ContainerBuilder();

        // Registered as synthetic so autowiring can resolve any
        // constructor that type-hints FrameworkConfig; the real
        // instance is injected right after compile() below (Symfony
        // DI does not allow instantiating synthetic services itself).
        $container->register(FrameworkConfig::class, FrameworkConfig::class)
            ->setSynthetic(true)
            ->setPublic(true);

        $container->register('aicrion.cache', CachePoolFactory::class)
            ->setFactory([CachePoolFactory::class, 'create'])
            ->setArguments([$this->config])
            ->setPublic(true);

        $this->httpClient = HttpClient::create();
        Telegram::configure($this->httpClient, $this->config->botToken);

        $packageManager = new PackageManager($this->config);
        $packageManager->discover();
        $packageManager->runPendingMigrations();

        $registry = $packageManager->intentFilterRegistry();

        $entityManager = EntityManagerFactory::create($this->config, $packageManager->entityClasses());
        $container->register(EntityManagerInterface::class, EntityManagerInterface::class)
            ->setSynthetic(true)
            ->setPublic(true);

        // The built-in "404 Activity" is always available, even on a
        // completely empty installation with zero plugins.
        $container->autowire(FallbackActivityMarker::class, FallbackActivityMarker::class)->setPublic(true);

        foreach ($packageManager->activityClasses() as $activityClass) {
            $container->autowire($activityClass, $activityClass)->setPublic(true);
        }

        $container->register(CallbackDataStore::class, CallbackDataStore::class)
            ->setArguments([new Reference('aicrion.cache')])
            ->setPublic(true);

        $container->register(IntentResolver::class, IntentResolver::class)
            ->setArguments([$registry, new Reference(CallbackDataStore::class)])
            ->setPublic(true);

        $container->register(BackStackStore::class, BackStackStore::class)
            ->setArguments([new Reference('aicrion.cache')])
            ->setPublic(true);

        $container->register(StateStore::class, StateStore::class)
            ->setArguments([new Reference('aicrion.cache')])
            ->setPublic(true);

        $container->register(ActivityManager::class, ActivityManager::class)
            ->setArguments([
                new Reference('service_container'),
                new Reference(IntentResolver::class),
                new Reference(BackStackStore::class),
                new Reference(StateStore::class),
            ])
            ->setPublic(true);

        $container->register(BroadcastDispatcher::class, BroadcastDispatcher::class)
            ->setArguments([new Reference('service_container')])
            ->setPublic(true);

        foreach ($packageManager->receiverClasses() as $receiverClass) {
            $container->autowire($receiverClass, $receiverClass)->setPublic(true);
        }

        // Symfony's ContainerBuilder already registers 'service_container'
        // as a synthetic service pointing to itself — calling set() on it
        // explicitly throws "You cannot set service \"service_container\"."
        // on modern Symfony versions, so it is intentionally omitted here.
        $container->compile();

        // Synthetic services must be set() after compile() completes.
        $container->set(FrameworkConfig::class, $this->config);
        $container->set(EntityManagerInterface::class, $entityManager);

        $this->container = $container;
        $this->activityManager = $container->get(ActivityManager::class);
        $this->container->get(BroadcastDispatcher::class)->registerReceivers($packageManager->receiverClasses());

        // Button/Keyboard render callback_data deep inside View::render(),
        // with no DI container in scope, so CallbackDataStore also needs
        // a statically-configured facade — same pattern as Telegram::configure()
        // above, and the same cache pool instance the DI-injected
        // CallbackDataStore(s) already use.
        CallbackDataStore::configure($container->get('aicrion.cache'));

        return $this;
    }

    public function handle(Update $update): ?View
    {
        if ($this->activityManager === null || $this->container === null) {
            throw new \LogicException('Kernel::boot() must be called before handle().');
        }

        $this->publishFirstSeenBroadcast($update);

        $result = $this->activityManager->dispatch($update);

        if ($result['view'] !== null) {
            $this->deliver($update, $result['view'], $result['intentFlags']);
        }

        return $result['view'];
    }

    /**
     * Sends the rendered View back to the chat that produced the
     * triggering Update, via the framework's fluent Telegram facade.
     */
    /** @param list<IntentFlag> $intentFlags */
    private function deliver(Update $update, View $view, array $intentFlags = []): void
    {
        $rendered = $view->render();

        Telegram::message()
            ->to($update->chatId)
            ->text($rendered['text'] ?? '')
            ->parseMode($view->parseMode ?? \Aicrion\Tandroid\View\ParseMode::Plain)
            ->markup($rendered['reply_markup'] ?? [])
            ->send();
    }

    /**
     * Publishes UserJoinedEvent the first time a chat_id is observed
     * by this installation, letting plugins hook onboarding logic
     * (e.g. WelcomeReceiver) without touching the ActivityManager.
     */
    private function publishFirstSeenBroadcast(Update $update): void
    {
        if ($update->chatId === 0 || $this->container === null) {
            return;
        }

        /** @var CacheItemPoolInterface $cache */
        $cache = $this->container->get('aicrion.cache');
        $item = $cache->getItem('aicrion.seen_chat.' . $update->chatId);

        if ($item->isHit()) {
            return;
        }

        $item->set(true);
        $cache->save($item);

        $this->container->get(BroadcastDispatcher::class)->publish(
            new UserJoinedEvent(userId: $update->userId, chatId: $update->chatId),
        );
    }

    public function httpClient(): HttpClientInterface
    {
        if ($this->httpClient === null) {
            throw new \LogicException('Kernel::boot() must be called before httpClient().');
        }

        return $this->httpClient;
    }

    public function config(): FrameworkConfig
    {
        return $this->config;
    }
}