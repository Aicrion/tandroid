<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api\Payments;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Wraps getStarTransactions and refundStarPayment for bookkeeping
 * and dispute-handling on Telegram Stars revenue — directly relevant
 * to payment-gateway style middleware that needs a reconciliation
 * job pulling settled transactions periodically.
 */
final class StarTransactions
{
    public function __construct(
        private readonly ?HttpClientInterface $client,
        private readonly string $token,
    ) {}

    public function list(int $offset = 0, int $limit = 100): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/getStarTransactions",
            ['json' => ['offset' => $offset, 'limit' => $limit]],
        );

        return $response->toArray()['result']['transactions'] ?? [];
    }

    public function refund(int $userId, string $telegramPaymentChargeId): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/refundStarPayment",
            ['json' => [
                'user_id' => $userId,
                'telegram_payment_charge_id' => $telegramPaymentChargeId,
            ]],
        );

        return $response->toArray();
    }
}
