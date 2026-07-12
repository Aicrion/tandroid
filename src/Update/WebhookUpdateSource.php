<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Update;

/**
 * Parses the raw JSON body of an incoming Telegram webhook HTTP
 * request into the framework's normalized Update DTO. Used directly
 * by public/webhook.php on shared hosting — no daemon required.
 */
final class WebhookUpdateSource implements UpdateSourceInterface
{
    public function __construct(
        private readonly string $rawBody,
    ) {}

    public function pull(): iterable
    {
        $payload = json_decode($this->rawBody, associative: true);

        if (!is_array($payload)) {
            return;
        }

        yield UpdateMapper::map($payload);
    }
}