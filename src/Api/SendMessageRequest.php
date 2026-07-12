<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api;

use Aicrion\Tandroid\View\ParseMode;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Fluent builder for the sendMessage Bot API method. Every setter
 * returns a new immutable instance so requests can be safely reused
 * as templates across the framework's Widget/View layer.
 */
final class SendMessageRequest
{
    private int|string|null $chatId = null;

    private ?string $text = null;

    private ?ParseMode $parseMode = ParseMode::Plain;

    private ?int $replyToMessageId = null;

    private array $replyMarkup = [];

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

    public function text(string $text): self
    {
        $clone = clone $this;
        $clone->text = $text;

        return $clone;
    }

    public function parseMode(ParseMode $mode): self
    {
        $clone = clone $this;
        $clone->parseMode = $mode;

        return $clone;
    }

    public function replyTo(int $messageId): self
    {
        $clone = clone $this;
        $clone->replyToMessageId = $messageId;

        return $clone;
    }

    public function markup(array $replyMarkup): self
    {
        $clone = clone $this;
        $clone->replyMarkup = $replyMarkup;

        return $clone;
    }

    public function send(): array
    {
        $payload = array_filter([
            'chat_id' => $this->chatId,
            'text' => $this->text,
            'parse_mode' => ($this->parseMode === null || $this->parseMode === ParseMode::Plain) ? null : $this->parseMode->value,
            'reply_to_message_id' => $this->replyToMessageId,
            'reply_markup' => $this->replyMarkup ?: null,
        ], static fn ($v) => $v !== null);

        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/sendMessage",
            ['json' => $payload],
        );

        return $response->toArray();
    }
}