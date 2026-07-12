<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Remote;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Sends a RemoteIntent to another bot via Telegram Bot API 10.0's
 * bot-to-bot messaging endpoint, and exposes a matching receive-side
 * hook so an incoming bot-to-bot message can be normalized back into
 * a RemoteIntent for the local IntentResolver/ActivityManager to
 * process just like a regular Update — multi-agent workflows become
 * plain Activities that happen to be triggered by another bot instead
 * of a human.
 */
final class BotToBotClient
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $token,
    ) {}

    public function send(RemoteIntent $intent): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/sendBotMessage",
            [
                'json' => [
                    'target_username' => $intent->targetBotUsername,
                    'message' => json_encode($intent->toArray(), JSON_THROW_ON_ERROR),
                ],
            ],
        );

        return $response->toArray();
    }

    public static function decodeIncoming(string $rawMessage): RemoteIntent
    {
        $decoded = json_decode($rawMessage, associative: true);
        $intent = RemoteIntent::to($decoded['from_bot'] ?? '', $decoded['action'] ?? '');

        foreach ($decoded['payload'] ?? [] as $key => $value) {
            $intent = $intent->with($key, $value);
        }

        return $intent;
    }
}