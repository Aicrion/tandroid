<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api\Gifts;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Wraps Telegram's gift-related methods (getAvailableGifts,
 * sendGift) letting a bot reward users with collectible/Stars-based
 * gifts — a lightweight loyalty/engagement mechanic.
 */
final class GiftManager
{
    public function __construct(
        private readonly ?HttpClientInterface $client,
        private readonly string $token,
    ) {}

    public function available(): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/getAvailableGifts",
        );

        return $response->toArray()['result']['gifts'] ?? [];
    }

    public function send(int $userId, string $giftId, ?string $text = null): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/sendGift",
            ['json' => array_filter([
                'user_id' => $userId,
                'gift_id' => $giftId,
                'text' => $text,
            ])],
        );

        return $response->toArray();
    }
}