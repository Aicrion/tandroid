<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Update;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Long-polls Telegram's getUpdates endpoint. Intended for a
 * persistent daemon process (bin/poll.php) on VPS/Docker
 * deployments, where a webhook endpoint isn't practical or desired.
 * Tracks the last processed update_id internally so restarts don't
 * replay old updates.
 */
final class PollingUpdateSource implements UpdateSourceInterface
{
    private int $offset = 0;

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $token,
        private readonly int $timeout = 30,
    ) {}

    public function pull(): iterable
    {
        $response = $this->client->request('GET', "https://api.telegram.org/bot{$this->token}/getUpdates", [
            'query' => [
                'offset' => $this->offset,
                'timeout' => $this->timeout,
            ],
            'timeout' => $this->timeout + 5,
        ]);

        $result = $response->toArray()['result'] ?? [];

        foreach ($result as $payload) {
            $this->offset = $payload['update_id'] + 1;

            yield UpdateMapper::map($payload);
        }
    }
}