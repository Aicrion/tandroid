<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api\Inline;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Fluent wrapper over answerInlineQuery, letting an Activity respond
 * to the "@bot ..." inline-mode interaction pattern — completely
 * separate from the Activity/Intent flow since there is no chat_id,
 * only a query string and a list of candidate results the user picks from.
 */
final class InlineQueryAnswer
{
    /** @var list<array<string, mixed>> */
    private array $results = [];

    private int $cacheTime = 300;

    public function __construct(
        private readonly ?HttpClientInterface $client,
        private readonly string $token,
        private readonly string $inlineQueryId,
    ) {}

    public function article(string $id, string $title, string $messageText): self
    {
        $clone = clone $this;
        $clone->results[] = [
            'type' => 'article',
            'id' => $id,
            'title' => $title,
            'input_message_content' => ['message_text' => $messageText],
        ];

        return $clone;
    }

    public function photo(string $id, string $photoUrl, string $thumbUrl): self
    {
        $clone = clone $this;
        $clone->results[] = [
            'type' => 'photo',
            'id' => $id,
            'photo_url' => $photoUrl,
            'thumbnail_url' => $thumbUrl,
        ];

        return $clone;
    }

    public function cacheFor(int $seconds): self
    {
        $clone = clone $this;
        $clone->cacheTime = $seconds;

        return $clone;
    }

    public function send(): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/answerInlineQuery",
            ['json' => [
                'inline_query_id' => $this->inlineQueryId,
                'results' => $this->results,
                'cache_time' => $this->cacheTime,
            ]],
        );

        return $response->toArray();
    }
}