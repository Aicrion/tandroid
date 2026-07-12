<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api\Invite;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Handles standard join-request approvals plus newer query/web-app
 * flows around chat join requests, giving the bot a unified join
 * gate for classic groups, forums, and mini-app driven onboarding.
 */
final class JoinRequestManager
{
    public function __construct(
        private readonly ?HttpClientInterface $client,
        private readonly string $token,
    ) {}

    public function approve(int|string $chatId, int $userId): array
    {
        return $this->call('approveChatJoinRequest', $chatId, $userId);
    }

    public function decline(int|string $chatId, int $userId): array
    {
        return $this->call('declineChatJoinRequest', $chatId, $userId);
    }

    public function answerQuery(string $queryId, bool $ok = true, ?string $message = null): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/answerChatJoinRequestQuery",
            ['json' => array_filter([
                'chat_join_request_query_id' => $queryId,
                'ok' => $ok,
                'message' => $message,
            ])],
        );

        return $response->toArray();
    }

    public function sendWebApp(int|string $chatId, string $text): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/sendChatJoinRequestWebApp",
            ['json' => ['chat_id' => $chatId, 'text' => $text]],
        );

        return $response->toArray();
    }

    private function call(string $method, int|string $chatId, int $userId): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/{$method}",
            ['json' => ['chat_id' => $chatId, 'user_id' => $userId]],
        );

        return $response->toArray();
    }
}
