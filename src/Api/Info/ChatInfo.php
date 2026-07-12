<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api\Info;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Read-only lookups (getChat, getChatMember, getChatAdministrators,
 * getChatMemberCount) — the informational counterpart to ChatAdmin's
 * mutating calls, commonly needed for permission checks inside an
 * Activity's onCreate before deciding whether to proceed.
 */
final class ChatInfo
{
    public function __construct(
        private readonly ?HttpClientInterface $client,
        private readonly string $token,
        private readonly int|string $chatId,
    ) {}

    public function get(): array
    {
        return $this->call('getChat', []);
    }

    public function member(int $userId): array
    {
        return $this->call('getChatMember', ['user_id' => $userId]);
    }

    public function administrators(): array
    {
        return $this->call('getChatAdministrators', [])['result'] ?? [];
    }

    public function memberCount(): int
    {
        return $this->call('getChatMemberCount', [])['result'] ?? 0;
    }

    private function call(string $method, array $params): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/{$method}",
            ['json' => ['chat_id' => $this->chatId, ...$params]],
        );

        return $response->toArray();
    }
}