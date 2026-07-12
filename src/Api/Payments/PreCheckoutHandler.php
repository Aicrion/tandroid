<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api\Payments;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Answers pre_checkout_query updates — Telegram requires an explicit
 * ok/error response within 10 seconds before it finalizes a Stars or
 * card payment, so this is wired directly into the Kernel's Update
 * dispatch path (a PreCheckoutQuery is treated like any other Update
 * type and routed to whichever Activity registered for it).
 */
final class PreCheckoutHandler
{
    public function __construct(
        private readonly ?HttpClientInterface $client,
        private readonly string $token,
    ) {}

    public function approve(string $preCheckoutQueryId): array
    {
        return $this->answer($preCheckoutQueryId, ok: true);
    }

    public function reject(string $preCheckoutQueryId, string $errorMessage): array
    {
        return $this->answer($preCheckoutQueryId, ok: false, errorMessage: $errorMessage);
    }

    private function answer(string $id, bool $ok, ?string $errorMessage = null): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/answerPreCheckoutQuery",
            ['json' => array_filter([
                'pre_checkout_query_id' => $id,
                'ok' => $ok,
                'error_message' => $errorMessage,
            ], static fn ($v) => $v !== null)],
        );

        return $response->toArray();
    }
}
