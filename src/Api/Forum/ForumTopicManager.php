<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api\Forum;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Wraps forum-topic management methods (createForumTopic,
 * editForumTopic, closeForumTopic, deleteForumTopic). Combined with
 * the messageThreadId already added to Update, this lets a plugin
 * both create dedicated topics per feature (e.g. "Support",
 * "Orders") and correctly route incoming messages back to the
 * Activity responsible for that topic.
 */
final class ForumTopicManager
{
    public function __construct(
        private readonly ?HttpClientInterface $client,
        private readonly string $token,
        private readonly int|string $chatId,
    ) {}

    public function create(string $name, ?int $iconColor = null): array
    {
        return $this->call('createForumTopic', array_filter([
            'name' => $name,
            'icon_color' => $iconColor,
        ]));
    }

    public function rename(int $messageThreadId, string $name): array
    {
        return $this->call('editForumTopic', [
            'message_thread_id' => $messageThreadId,
            'name' => $name,
        ]);
    }

    public function close(int $messageThreadId): array
    {
        return $this->call('closeForumTopic', ['message_thread_id' => $messageThreadId]);
    }

    public function reopen(int $messageThreadId): array
    {
        return $this->call('reopenForumTopic', ['message_thread_id' => $messageThreadId]);
    }

    public function delete(int $messageThreadId): array
    {
        return $this->call('deleteForumTopic', ['message_thread_id' => $messageThreadId]);
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