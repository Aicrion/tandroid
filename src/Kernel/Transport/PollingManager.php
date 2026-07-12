<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Kernel\Transport;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Long-polling transport for getUpdates. Useful for local development
 * and simple deployments without public HTTPS/webhook infra.
 */
final class PollingManager
{
    public function __construct(
        private readonly ?HttpClientInterface $client,
        private readonly string $token,
    ) {}

    public function updates(int $offset = 0, int $timeout = 30, array $allowedUpdates = []): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/getUpdates",
            ['json' => array_filter([
                'offset' => $offset ?: null,
                'timeout' => $timeout,
                'allowed_updates' => $allowedUpdates ?: null,
            ])],
        );

        return $response->toArray()['result'] ?? [];
    }
}