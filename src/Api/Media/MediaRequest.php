<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api\Media;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Shared fluent base for every sendX media method (photo, video,
 * document, audio, voice, animation). Subclasses only declare their
 * Bot API method name and media field key; all common concerns
 * (caption, parse_mode, reply, thread routing) live here once.
 */
abstract class MediaRequest
{
    protected int|string|null $chatId = null;

    protected string|null $media = null;

    protected ?string $caption = null;

    protected ?string $parseMode = 'MarkdownV2';

    protected ?int $replyToMessageId = null;

    protected ?int $messageThreadId = null;

    protected array $extra = [];

    public function __construct(
        protected readonly ?HttpClientInterface $client,
        protected readonly string $token,
    ) {}

    abstract protected function method(): string;

    abstract protected function mediaField(): string;

    public function to(int|string $chatId): static
    {
        $clone = clone $this;
        $clone->chatId = $chatId;

        return $clone;
    }

    public function media(string $fileIdOrUrlOrPath): static
    {
        $clone = clone $this;
        $clone->media = $fileIdOrUrlOrPath;

        return $clone;
    }

    public function caption(string $caption): static
    {
        $clone = clone $this;
        $clone->caption = $caption;

        return $clone;
    }

    public function replyTo(int $messageId): static
    {
        $clone = clone $this;
        $clone->replyToMessageId = $messageId;

        return $clone;
    }

    public function inThread(int $messageThreadId): static
    {
        $clone = clone $this;
        $clone->messageThreadId = $messageThreadId;

        return $clone;
    }

    public function with(array $extra): static
    {
        $clone = clone $this;
        $clone->extra = [...$clone->extra, ...$extra];

        return $clone;
    }

    public function send(): array
    {
        $payload = array_filter([
            'chat_id' => $this->chatId,
            $this->mediaField() => $this->media,
            'caption' => $this->caption,
            'parse_mode' => $this->parseMode,
            'reply_to_message_id' => $this->replyToMessageId,
            'message_thread_id' => $this->messageThreadId,
            ...$this->extra,
        ], static fn ($v) => $v !== null);

        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/{$this->method()}",
            ['json' => $payload],
        );

        return $response->toArray();
    }
}
