<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Update;

/**
 * Single normalization point shared by both PollingUpdateSource and
 * WebhookUpdateSource, guaranteeing the Kernel always receives an
 * identical Update DTO shape regardless of transport.
 */
final class UpdateMapper
{
    public static function map(array $payload): Update
    {
        [$type, $chatId, $userId, $text, $callbackData] = match (true) {
            isset($payload['message']) => [
                UpdateType::Message,
                $payload['message']['chat']['id'],
                $payload['message']['from']['id'],
                $payload['message']['text'] ?? null,
                null,
            ],
            isset($payload['callback_query']) => [
                UpdateType::CallbackQuery,
                $payload['callback_query']['message']['chat']['id'],
                $payload['callback_query']['from']['id'],
                null,
                $payload['callback_query']['data'] ?? null,
            ],
            isset($payload['pre_checkout_query']) => [
                UpdateType::PreCheckoutQuery,
                0,
                $payload['pre_checkout_query']['from']['id'],
                $payload['pre_checkout_query']['id'] ?? null,
                null,
            ],
            isset($payload['inline_query']) => [
                UpdateType::InlineQuery,
                0,
                $payload['inline_query']['from']['id'],
                $payload['inline_query']['query'] ?? null,
                null,
            ],
            default => [UpdateType::Unknown, 0, 0, null, null],
        };

        $threadId = $payload['message']['message_thread_id']
            ?? $payload['callback_query']['message']['message_thread_id']
            ?? null;

        return new Update(
            updateId: $payload['update_id'] ?? 0,
            chatId: $chatId,
            userId: $userId,
            type: $type,
            text: $text,
            callbackData: $callbackData,
            messageThreadId: $threadId,
            raw: $payload,
        );
    }
}
