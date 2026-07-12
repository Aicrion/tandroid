<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ReactionRequest
{
    private array $emojis = [];

    private bool $isBig = false;

    public function __construct(
        private readonly ?HttpClientInterface $client,
        private readonly string $token,
        private readonly int $chatId,
        private readonly int $messageId,
    ) {}

    public function emoji(string ...$emoji): self
    {
        $clone = clone $this;
        $clone->emojis = array_map(static fn (string $e) => ['type' => 'emoji', 'emoji' => $e], $emoji);

        return $clone;
    }

    public function big(bool $big = true): self
    {
        $clone = clone $this;
        $clone->isBig = $big;

        return $clone;
    }

    public function send(): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/setMessageReaction",
            ['json' => [
                'chat_id' => $this->chatId,
                'message_id' => $this->messageId,
                'reaction' => $this->emojis,
                'is_big' => $this->isBig,
            ]],
        );

        return $response->toArray();
    }

    public function clear(): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/setMessageReaction",
            ['json' => [
                'chat_id' => $this->chatId,
                'message_id' => $this->messageId,
                'reaction' => [],
            ]],
        );

        return $response->toArray();
    }

    public function deleteAll(): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/deleteAllMessageReactions",
            ['json' => [
                'chat_id' => $this->chatId,
                'message_id' => $this->messageId,
            ]],
        );

        return $response->toArray();
    }
}
