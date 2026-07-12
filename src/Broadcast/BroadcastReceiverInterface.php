<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Broadcast;

/**
 * Contract for a system-wide event listener, analogous to Android's
 * android.content.BroadcastReceiver. Registered against a specific
 * event class via #[BroadcastFilter] and invoked by BroadcastDispatcher
 * whenever that event is published, regardless of which plugin fired it.
 */
interface BroadcastReceiverInterface
{
    public function onReceive(object $event): void;
}