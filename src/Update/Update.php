<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Update;

/**
 * Normalized, source-agnostic representation of an incoming Telegram
 * update. Both PollingUpdateSource and WebhookUpdateSource must
 * produce this same DTO so the Kernel never knows which transport was used.
 */
final class Update
{
    public function __construct(
        public readonly int $updateId,
        public readonly int $chatId,
        public readonly int $userId,
        public readonly UpdateType $type,
        public readonly ?string $text = null,
        public readonly ?string $callbackData = null,
        public readonly ?int $messageThreadId = null,
        public readonly array $raw = [],
    ) {}
}