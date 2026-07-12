<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api\Managed;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Completes the managed-bot surface with access-settings methods.
 */
final class ManagedBotAccess
{
    public function __construct(
        private readonly ?HttpClientInterface $client,
        private readonly string $token,
    ) {}

    public function get(int $botId): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/getManagedBotAccessSettings",
            ['json' => ['bot_id' => $botId]],
        );

        return $response->toArray()['result'] ?? [];
    }

    public function set(int $botId, array $settings): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/setManagedBotAccessSettings",
            ['json' => ['bot_id' => $botId, 'settings' => $settings]],
        );

        return $response->toArray();
    }
}
