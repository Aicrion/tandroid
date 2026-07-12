<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api\Media;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class SendDiceRequest
{
    private int|string|null $chatId = null;

    private string $emoji = '🎲';

    public function __construct(
        private readonly ?HttpClientInterface $client,
        private readonly string $token,
    ) {}

    public function to(int|string $chatId): self
    {
        $clone = clone $this;
        $clone->chatId = $chatId;

        return $clone;
    }

    public function emoji(string $emoji): self
    {
        $clone = clone $this;
        $clone->emoji = $emoji;

        return $clone;
    }

    public function send(): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/sendDice",
            ['json' => ['chat_id' => $this->chatId, 'emoji' => $this->emoji]],
        );

        return $response->toArray();
    }
}
