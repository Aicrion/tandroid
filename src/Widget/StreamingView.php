<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Widget;

/**
 * Wraps Telegram Bot API 9.5's sendMessageDraft, letting an Activity
 * push incremental text chunks to the user (e.g. token-by-token from
 * an LLM) the same way a ChatGPT-style UI streams a response. The
 * StreamingView tracks the accumulated buffer and message reference
 * so successive chunk() calls edit the same in-flight draft instead
 * of sending a new message each time.
 */
final class StreamingView
{
    private string $buffer = '';

    private ?int $draftMessageId = null;

    private function __construct(
        private readonly int $chatId,
    ) {}

    public static function forChat(int $chatId): self
    {
        return new self($chatId);
    }

    /**
     * Appends a chunk of generated text and returns the sendMessageDraft
     * payload to dispatch. Callers pipe this into their HTTP client of
     * choice (typically via Api\Telegram).
     */
    public function chunk(string $text): array
    {
        $this->buffer .= $text;

        return array_filter([
            'chat_id' => $this->chatId,
            'text' => $this->buffer,
            'draft_message_id' => $this->draftMessageId,
        ], static fn ($v) => $v !== null);
    }

    public function withDraftMessageId(int $messageId): self
    {
        $clone = clone $this;
        $clone->draftMessageId = $messageId;

        return $clone;
    }

    /**
     * Finalizes the stream — converts the draft into a permanent
     * sendMessage payload once generation completes.
     */
    public function finish(): array
    {
        return [
            'chat_id' => $this->chatId,
            'text' => $this->buffer,
        ];
    }
}