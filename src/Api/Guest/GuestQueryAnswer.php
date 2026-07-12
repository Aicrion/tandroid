<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api\Guest;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Wraps Bot API 10.0/10.1's answerGuestQuery — the required response
 * channel when a bot is invoked via @mention inside a chat it was
 * never added to (Guest Mode). Unlike sendMessage, this posts the
 * reply as a reply to the exact triggering message only, since the
 * bot has no ongoing presence in that chat.
 */
final class GuestQueryAnswer
{
    public function __construct(
        private readonly ?HttpClientInterface $client,
        private readonly string $token,
        private readonly string $guestQueryId,
    ) {}

    public function reply(string $text, ?string $parseMode = 'MarkdownV2'): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/answerGuestQuery",
            ['json' => array_filter([
                'guest_query_id' => $this->guestQueryId,
                'text' => $text,
                'parse_mode' => $parseMode,
            ])],
        );

        return $response->toArray();
    }
}