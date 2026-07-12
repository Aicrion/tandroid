<?php

declare(strict_types=1);

namespace Greeter;

use Aicrion\Tandroid\Attribute\BroadcastFilter;
use Aicrion\Tandroid\Broadcast\BroadcastReceiverInterface;
use Aicrion\Tandroid\Broadcast\Event\UserJoinedEvent;

#[BroadcastFilter(event: UserJoinedEvent::class)]
final class WelcomeReceiver implements BroadcastReceiverInterface
{
    public function onReceive(object $event): void
    {
        /** @var UserJoinedEvent $event */
        // e.g. Telegram::message()->to($event->chatId)->text('خوش آمدی!')->send();
    }
}
