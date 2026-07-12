<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api\Business;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Wraps Telegram Business Account methods, letting a bot connected
 * to a human user's Business account read/reply on their behalf and
 * manage their Stars balance transfer — a niche but growing surface
 * for premium/CRM-style bots.
 */
final class BusinessAccount
{
    public function __construct(
        private readonly ?HttpClientInterface $client,
        private readonly string $token,
        private readonly string $businessConnectionId,
    ) {}

    public function setName(string $firstName, ?string $lastName = null): array
    {
        return $this->call('setBusinessAccountName', array_filter([
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]));
    }

    public function readMessage(int $chatId, int $messageId): array
    {
        return $this->call('readBusinessMessage', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ]);
    }

    public function transferStars(int $starCount): array
    {
        return $this->call('transferBusinessAccountStars', ['star_count' => $starCount]);
    }

    private function call(string $method, array $params): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/{$method}",
            ['json' => ['business_connection_id' => $this->businessConnectionId, ...$params]],
        );

        return $response->toArray();
    }
}