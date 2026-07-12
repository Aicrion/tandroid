<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api\Media;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Wraps sendMediaGroup for posting an album of 2-10 photos/videos
 * as a single grouped message.
 */
final class MediaGroupRequest
{
    private int|string|null $chatId = null;

    /** @var list<array{type: string, media: string}> */
    private array $items = [];

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

    public function photo(string $media, ?string $caption = null): self
    {
        $clone = clone $this;
        $clone->items[] = array_filter(['type' => 'photo', 'media' => $media, 'caption' => $caption]);

        return $clone;
    }

    public function video(string $media, ?string $caption = null): self
    {
        $clone = clone $this;
        $clone->items[] = array_filter(['type' => 'video', 'media' => $media, 'caption' => $caption]);

        return $clone;
    }

    public function send(): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/sendMediaGroup",
            ['json' => ['chat_id' => $this->chatId, 'media' => $this->items]],
        );

        return $response->toArray();
    }
}