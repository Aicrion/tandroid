<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Wraps answerCallbackQuery — mandatory after handling any inline
 * button tap, otherwise Telegram keeps showing a loading spinner on
 * the client until it times out. The ActivityManager calls a default
 * empty answer automatically after every CallbackQuery dispatch, but
 * Activities can override it (e.g. to show a toast alert) via this class.
 */
final class CallbackQueryAnswer
{
    private ?string $text = null;

    private bool $showAlert = false;

    public function __construct(
        private readonly ?HttpClientInterface $client,
        private readonly string $token,
        private readonly string $callbackQueryId,
    ) {}

    public function toast(string $text, bool $asAlert = false): self
    {
        $clone = clone $this;
        $clone->text = $text;
        $clone->showAlert = $asAlert;

        return $clone;
    }

    public function send(): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/answerCallbackQuery",
            ['json' => array_filter([
                'callback_query_id' => $this->callbackQueryId,
                'text' => $this->text,
                'show_alert' => $this->showAlert ?: null,
            ])],
        );

        return $response->toArray();
    }
}