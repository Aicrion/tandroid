<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Broadcast;

use Aicrion\Tandroid\Attribute\BroadcastFilter;
use Psr\Container\ContainerInterface;

/**
 * Wraps Symfony's EventDispatcher but resolves listeners lazily
 * through the DI container and matches them purely by scanning
 * #[BroadcastFilter] attributes declared on receiver classes —
 * mirroring how Android's system server matches broadcast Intents
 * against every <receiver> in every installed app's manifest.
 */
final class BroadcastDispatcher
{
    /** @var array<class-string, list<class-string>> event class => receiver classes */
    private array $registry = [];

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    /**
     * @param list<class-string> $receiverClasses
     */
    public function registerReceivers(array $receiverClasses): void
    {
        foreach ($receiverClasses as $receiverClass) {
            $reflection = new \ReflectionClass($receiverClass);

            foreach ($reflection->getAttributes(BroadcastFilter::class) as $attribute) {
                /** @var BroadcastFilter $filter */
                $filter = $attribute->newInstance();
                $this->registry[$filter->event][] = $receiverClass;
            }
        }
    }

    public function publish(object $event): void
    {
        $eventClass = $event::class;

        foreach ($this->registry[$eventClass] ?? [] as $receiverClass) {
            /** @var BroadcastReceiverInterface $receiver */
            $receiver = $this->container->get($receiverClass);
            $receiver->onReceive($event);
        }
    }
}