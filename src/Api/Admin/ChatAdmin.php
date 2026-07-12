<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api\Admin;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Fluent facade over Telegram's chat-administration methods
 * (ban/restrict/promote members, pin messages, edit chat metadata).
 * Scoped to a single chat_id so an Activity handling a moderation
 * command can chain calls naturally: ChatAdmin::for($chatId)->ban($userId).
 */
final class ChatAdmin
{
    public function __construct(
        private readonly ?HttpClientInterface $client,
        private readonly string $token,
        private readonly int|string $chatId,
    ) {}

    public static function for_(HttpClientInterface $client, string $token, int|string $chatId): self
    {
        return new self($client, $token, $chatId);
    }

    public function ban(int $userId, ?int $untilDate = null): array
    {
        return $this->call('banChatMember', array_filter([
            'user_id' => $userId,
            'until_date' => $untilDate,
        ]));
    }

    public function unban(int $userId): array
    {
        return $this->call('unbanChatMember', ['user_id' => $userId]);
    }

    public function restrict(int $userId, ChatPermissions $permissions, ?int $untilDate = null): array
    {
        return $this->call('restrictChatMember', array_filter([
            'user_id' => $userId,
            'permissions' => $permissions->toArray(),
            'until_date' => $untilDate,
        ]));
    }

    public function promote(int $userId, array $rights = []): array
    {
        return $this->call('promoteChatMember', ['user_id' => $userId, ...$rights]);
    }

    public function pin(int $messageId, bool $notify = false): array
    {
        return $this->call('pinChatMessage', [
            'message_id' => $messageId,
            'disable_notification' => !$notify,
        ]);
    }

    public function unpin(?int $messageId = null): array
    {
        return $this->call('unpinChatMessage', array_filter(['message_id' => $messageId]));
    }

    public function setTitle(string $title): array
    {
        return $this->call('setChatTitle', ['title' => $title]);
    }

    public function setDescription(string $description): array
    {
        return $this->call('setChatDescription', ['description' => $description]);
    }

    public function setPermissions(ChatPermissions $permissions): array
    {
        return $this->call('setChatPermissions', ['permissions' => $permissions->toArray()]);
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