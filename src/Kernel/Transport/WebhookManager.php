<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Kernel\Transport;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Owns the webhook lifecycle: setWebhook, deleteWebhook,
 * getWebhookInfo. This is the transport layer that makes the bot
 * actually receive updates in production, so Kernel bootstrap can
 * choose between webhook and long polling based on environment.
 */
final class WebhookManager
{
    public function __construct(
        private readonly ?HttpClientInterface $client,
        private readonly string $token,
    ) {}

    public function set(string $url, ?string $secretToken = null, array $allowedUpdates = [], bool $dropPendingUpdates = false): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/setWebhook",
            ['json' => array_filter([
                'url' => $url,
                'secret_token' => $secretToken,
                'allowed_updates' => $allowedUpdates ?: null,
                'drop_pending_updates' => $dropPendingUpdates ?: null,
            ])],
        );

        return $response->toArray();
    }

    public function delete(bool $dropPendingUpdates = false): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/deleteWebhook",
            ['json' => array_filter(['drop_pending_updates' => $dropPendingUpdates ?: null])],
        );

        return $response->toArray();
    }

    public function info(): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/getWebhookInfo",
        );

        return $response->toArray()['result'] ?? [];
    }
}
