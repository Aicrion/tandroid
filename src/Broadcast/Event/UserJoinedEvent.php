<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Broadcast\Event;

/**
 * Published by the Kernel the first time a chat_id is seen, before
 * any Activity handles the triggering Update — mirrors Android's
 * ACTION_PACKAGE_ADDED style system broadcasts.
 */
final class UserJoinedEvent
{
    public function __construct(
        public readonly int $userId,
        public readonly int $chatId,
    ) {}
}