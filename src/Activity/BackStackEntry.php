<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Activity;

/**
 * A single frame in a user's Activity back-stack, persisted between
 * requests (Redis by default). Enables the "virtual Back button"
 * behavior across stateless HTTP/webhook requests.
 */
final class BackStackEntry
{
    public function __construct(
        public readonly string $activityClass,
        public readonly array $extras = [],
        public readonly int $messageId = 0,
    ) {}
}
