<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Fluent wrapper covering editMessageText, editMessageCaption,
 * editMessageMedia, editMessageReplyMarkup, and deleteMessage —
 * essential for the WindowManager-style "in-place update" pattern
 * (e.g. re-rendering a Wizard page or a checkbox toggle by editing
 * the existing message instead of sending a new one each time).
 */
final class MessageEditor
{
    public function __construct(
        private readonly ?HttpClientInterface $client,
        private readonly string $token,
        private readonly int|string $chatId,
        private readonly int $messageId,
    ) {}

    public function text(string $text, ?array $replyMarkup = null, ?string $parseMode = 'MarkdownV2'): array
    {
        return $this->call('editMessageText', array_filter([
            'text' => $text,
            'parse_mode' => $parseMode,
            'reply_markup' => $replyMarkup,
        ]));
    }

    public function caption(string $caption): array
    {
        return $this->call('editMessageCaption', ['caption' => $caption]);
    }

    public function media(array $media, ?array $replyMarkup = null): array
    {
        return $this->call('editMessageMedia', array_filter([
            'media' => $media,
            'reply_markup' => $replyMarkup,
        ]));
    }

    public function replyMarkup(array $replyMarkup): array
    {
        return $this->call('editMessageReplyMarkup', ['reply_markup' => $replyMarkup]);
    }

    public function delete(): array
    {
        return $this->call('deleteMessage', []);
    }

    private function call(string $method, array $params): array
    {
        $response = $this->client->request(
            'POST',
            "https://api.telegram.org/bot{$this->token}/{$method}",
            ['json' => [
                'chat_id' => $this->chatId,
                'message_id' => $this->messageId,
                ...$params,
            ]],
        );

        return $response->toArray();
    }
}
