<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Wraps forwardMessage and copyMessage — forwarding preserves the
 * "Forwarded from" attribution, copying creates an independent
 * message with the same content but no attribution, useful for
 * relaying user-submitted content anonymously.
 */
final class MessageForwarder
{
    public function __construct(
        private readonly ?HttpClientInterface $client,
        private readonly string $token,
        private readonly int|string $fromChatId,
        private readonly int $messageId,
    ) {}

    public function forwardTo(int|string $chatId): array
    {
        return $this->call('forwardMessage', $chatId);
    }

    public function copyTo(int|string $chatId): array
    {
        return $this->call('copyMessage', $chatId);
    }

    private function call(string $method, int|string $chatId): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/{$method}",
            ['json' => [
                'chat_id' => $chatId,
                'from_chat_id' => $this->fromChatId,
                'message_id' => $this->messageId,
            ]],
        );

        return $response->toArray();
    }
}