<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Wraps sendChatAction — shows a transient "typing…"/"uploading
 * photo…" indicator. Widgets that trigger a slow operation (e.g. a
 * FormWidget step calling out to an external API) should send this
 * before starting work to keep the UX responsive.
 */
final class ChatActionRequest
{
    public function __construct(
        private readonly ?HttpClientInterface $client,
        private readonly string $token,
    ) {}

    public function typing(int|string $chatId): array
    {
        return $this->send($chatId, 'typing');
    }

    public function uploadingPhoto(int|string $chatId): array
    {
        return $this->send($chatId, 'upload_photo');
    }

    public function send(int|string $chatId, string $action): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/sendChatAction",
            ['json' => ['chat_id' => $chatId, 'action' => $action]],
        );

        return $response->toArray();
    }
}