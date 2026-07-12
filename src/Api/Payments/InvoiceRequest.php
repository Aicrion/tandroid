<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api\Payments;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Fluent builder for sendInvoice, targeting Telegram Stars as the
 * currency by default (currency "XTR") — the native digital-goods
 * payment rail introduced across 2024-2026 Bot API releases
 * [web:37][web:39]. Falls back to a real currency + provider_token
 * for traditional payment-gateway integrations when set explicitly,
 * which fits directly into the user's existing payment-gateway work.
 */
final class InvoiceRequest
{
    private int|string|null $chatId = null;

    private ?string $title = null;

    private ?string $description = null;

    private ?string $payload = null;

    private string $currency = 'XTR';

    private ?string $providerToken = null;

    /** @var list<array{label: string, amount: int}> */
    private array $prices = [];

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

    public function title(string $title): self
    {
        $clone = clone $this;
        $clone->title = $title;

        return $clone;
    }

    public function description(string $description): self
    {
        $clone = clone $this;
        $clone->description = $description;

        return $clone;
    }

    public function payload(string $payload): self
    {
        $clone = clone $this;
        $clone->payload = $payload;

        return $clone;
    }

    public function priceInStars(int $stars): self
    {
        $clone = clone $this;
        $clone->currency = 'XTR';
        $clone->prices = [['label' => 'Total', 'amount' => $stars]];

        return $clone;
    }

    public function priceInCurrency(string $currency, string $providerToken, int $amountMinorUnits, string $label = 'Total'): self
    {
        $clone = clone $this;
        $clone->currency = $currency;
        $clone->providerToken = $providerToken;
        $clone->prices = [['label' => $label, 'amount' => $amountMinorUnits]];

        return $clone;
    }

    public function send(): array
    {
        $payload = array_filter([
            'chat_id' => $this->chatId,
            'title' => $this->title,
            'description' => $this->description,
            'payload' => $this->payload,
            'provider_token' => $this->providerToken ?? '',
            'currency' => $this->currency,
            'prices' => $this->prices,
        ], static fn ($v) => $v !== null);

        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/sendInvoice",
            ['json' => $payload],
        );

        return $response->toArray();
    }

    public function createLink(): string
    {
        $payload = array_filter([
            'title' => $this->title,
            'description' => $this->description,
            'payload' => $this->payload,
            'provider_token' => $this->providerToken ?? '',
            'currency' => $this->currency,
            'prices' => $this->prices,
        ], static fn ($v) => $v !== null);

        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/createInvoiceLink",
            ['json' => $payload],
        );

        return $response->toArray()['result'];
    }
}
