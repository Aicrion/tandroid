<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api;

use Aicrion\Tandroid\View\ParseMode;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Fluent, type-safe entry point to the Telegram Bot API. Each call
 * (message(), photo(), editMessage() ...) returns a dedicated
 * request builder covering every official parameter with enums
 * instead of magic strings, then send()s over Symfony HttpClient.
 */
final class Telegram
{
    private static ?HttpClientInterface $client = null;

    private static string $token = '';

    public static function configure(HttpClientInterface $client, string $token): void
    {
        self::$client = $client;
        self::$token = $token;
    }

    public static function message(): SendMessageRequest
    {
        return new SendMessageRequest(self::$client, self::$token);
    }

    public static function reaction(int $chatId, int $messageId): ReactionRequest
    {
        return new ReactionRequest(self::$client, self::$token, $chatId, $messageId);
    }

    public static function photo(): \Aicrion\Tandroid\Api\Media\SendPhotoRequest
    {
        return new \Aicrion\Tandroid\Api\Media\SendPhotoRequest(self::$client, self::$token);
    }

    public static function video(): \Aicrion\Tandroid\Api\Media\SendVideoRequest
    {
        return new \Aicrion\Tandroid\Api\Media\SendVideoRequest(self::$client, self::$token);
    }

    public static function document(): \Aicrion\Tandroid\Api\Media\SendDocumentRequest
    {
        return new \Aicrion\Tandroid\Api\Media\SendDocumentRequest(self::$client, self::$token);
    }

    public static function audio(): \Aicrion\Tandroid\Api\Media\SendAudioRequest
    {
        return new \Aicrion\Tandroid\Api\Media\SendAudioRequest(self::$client, self::$token);
    }

    public static function voice(): \Aicrion\Tandroid\Api\Media\SendVoiceRequest
    {
        return new \Aicrion\Tandroid\Api\Media\SendVoiceRequest(self::$client, self::$token);
    }

    public static function animation(): \Aicrion\Tandroid\Api\Media\SendAnimationRequest
    {
        return new \Aicrion\Tandroid\Api\Media\SendAnimationRequest(self::$client, self::$token);
    }

    public static function location(): \Aicrion\Tandroid\Api\Media\SendLocationRequest
    {
        return new \Aicrion\Tandroid\Api\Media\SendLocationRequest(self::$client, self::$token);
    }

    public static function poll(): \Aicrion\Tandroid\Api\Media\SendPollRequest
    {
        return new \Aicrion\Tandroid\Api\Media\SendPollRequest(self::$client, self::$token);
    }

    public static function chat(int|string $chatId): \Aicrion\Tandroid\Api\Admin\ChatAdmin
    {
        return \Aicrion\Tandroid\Api\Admin\ChatAdmin::for_(self::$client, self::$token, $chatId);
    }

    public static function invoice(): \Aicrion\Tandroid\Api\Payments\InvoiceRequest
    {
        return new \Aicrion\Tandroid\Api\Payments\InvoiceRequest(self::$client, self::$token);
    }

    public static function preCheckout(): \Aicrion\Tandroid\Api\Payments\PreCheckoutHandler
    {
        return new \Aicrion\Tandroid\Api\Payments\PreCheckoutHandler(self::$client, self::$token);
    }

    public static function starTransactions(): \Aicrion\Tandroid\Api\Payments\StarTransactions
    {
        return new \Aicrion\Tandroid\Api\Payments\StarTransactions(self::$client, self::$token);
    }

    public static function inline(string $inlineQueryId): \Aicrion\Tandroid\Api\Inline\InlineQueryAnswer
    {
        return new \Aicrion\Tandroid\Api\Inline\InlineQueryAnswer(self::$client, self::$token, $inlineQueryId);
    }

    public static function webApp(string $webAppQueryId): \Aicrion\Tandroid\Api\Inline\WebAppQueryAnswer
    {
        return new \Aicrion\Tandroid\Api\Inline\WebAppQueryAnswer(self::$client, self::$token, $webAppQueryId);
    }

    public static function forum(int|string $chatId): \Aicrion\Tandroid\Api\Forum\ForumTopicManager
    {
        return new \Aicrion\Tandroid\Api\Forum\ForumTopicManager(self::$client, self::$token, $chatId);
    }

    public static function business(string $businessConnectionId): \Aicrion\Tandroid\Api\Business\BusinessAccount
    {
        return new \Aicrion\Tandroid\Api\Business\BusinessAccount(self::$client, self::$token, $businessConnectionId);
    }

    public static function gifts(): \Aicrion\Tandroid\Api\Gifts\GiftManager
    {
        return new \Aicrion\Tandroid\Api\Gifts\GiftManager(self::$client, self::$token);
    }

    public static function stickers(): \Aicrion\Tandroid\Api\Stickers\StickerSetManager
    {
        return new \Aicrion\Tandroid\Api\Stickers\StickerSetManager(self::$client, self::$token);
    }

    public static function callback(string $callbackQueryId): CallbackQueryAnswer
    {
        return new CallbackQueryAnswer(self::$client, self::$token, $callbackQueryId);
    }

    public static function edit(int|string $chatId, int $messageId): MessageEditor
    {
        return new MessageEditor(self::$client, self::$token, $chatId, $messageId);
    }

    public static function message_(int|string $fromChatId, int $messageId): MessageForwarder
    {
        return new MessageForwarder(self::$client, self::$token, $fromChatId, $messageId);
    }

    public static function chatInfo(int|string $chatId): \Aicrion\Tandroid\Api\Info\ChatInfo
    {
        return new \Aicrion\Tandroid\Api\Info\ChatInfo(self::$client, self::$token, $chatId);
    }

    public static function botInfo(): \Aicrion\Tandroid\Api\Info\BotInfo
    {
        return new \Aicrion\Tandroid\Api\Info\BotInfo(self::$client, self::$token);
    }

    public static function commands(): \Aicrion\Tandroid\Api\Menu\CommandMenu
    {
        return new \Aicrion\Tandroid\Api\Menu\CommandMenu(self::$client, self::$token);
    }

    public static function inviteLinks(int|string $chatId): \Aicrion\Tandroid\Api\Admin\ChatInviteLinkManager
    {
        return new \Aicrion\Tandroid\Api\Admin\ChatInviteLinkManager(self::$client, self::$token, $chatId);
    }

    public static function contact(): \Aicrion\Tandroid\Api\Media\SendContactRequest
    {
        return new \Aicrion\Tandroid\Api\Media\SendContactRequest(self::$client, self::$token);
    }

    public static function venue(): \Aicrion\Tandroid\Api\Media\SendVenueRequest
    {
        return new \Aicrion\Tandroid\Api\Media\SendVenueRequest(self::$client, self::$token);
    }

    public static function videoNote(): \Aicrion\Tandroid\Api\Media\SendVideoNoteRequest
    {
        return new \Aicrion\Tandroid\Api\Media\SendVideoNoteRequest(self::$client, self::$token);
    }

    public static function dice(): \Aicrion\Tandroid\Api\Media\SendDiceRequest
    {
        return new \Aicrion\Tandroid\Api\Media\SendDiceRequest(self::$client, self::$token);
    }

    public static function mediaGroup(): \Aicrion\Tandroid\Api\Media\MediaGroupRequest
    {
        return new \Aicrion\Tandroid\Api\Media\MediaGroupRequest(self::$client, self::$token);
    }

    public static function chatAction(): ChatActionRequest
    {
        return new ChatActionRequest(self::$client, self::$token);
    }

    public static function guestQuery(string $guestQueryId): \Aicrion\Tandroid\Api\Guest\GuestQueryAnswer
    {
        return new \Aicrion\Tandroid\Api\Guest\GuestQueryAnswer(self::$client, self::$token, $guestQueryId);
    }

    public static function webhook(): \Aicrion\Tandroid\Kernel\Transport\WebhookManager
    {
        return new \Aicrion\Tandroid\Kernel\Transport\WebhookManager(self::$client, self::$token);
    }

    public static function polling(): \Aicrion\Tandroid\Kernel\Transport\PollingManager
    {
        return new \Aicrion\Tandroid\Kernel\Transport\PollingManager(self::$client, self::$token);
    }

    public static function joinRequests(): \Aicrion\Tandroid\Api\Invite\JoinRequestManager
    {
        return new \Aicrion\Tandroid\Api\Invite\JoinRequestManager(self::$client, self::$token);
    }

    public static function managedAccess(): \Aicrion\Tandroid\Api\Managed\ManagedBotAccess
    {
        return new \Aicrion\Tandroid\Api\Managed\ManagedBotAccess(self::$client, self::$token);
    }
}