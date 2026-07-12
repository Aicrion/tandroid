<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api\Inline;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Wraps answerWebAppQuery, used when a Telegram Mini App running
 * inside the client sends data back to the bot via its own
 * inline-query-like channel rather than a regular Update.
 */
final class WebAppQueryAnswer
{
    public function __construct(
        private readonly ?HttpClientInterface $client,
        private readonly string $token,
        private readonly string $webAppQueryId,
    ) {}

    public function withArticle(string $id, string $title, string $messageText): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/answerWebAppQuery",
            ['json' => [
                'web_app_query_id' => $this->webAppQueryId,
                'result' => [
                    'type' => 'article',
                    'id' => $id,
                    'title' => $title,
                    'input_message_content' => ['message_text' => $messageText],
                ],
            ]],
        );

        return $response->toArray();
    }
}
