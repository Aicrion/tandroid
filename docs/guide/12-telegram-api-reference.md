# Complete Telegram API Reference

The static `Api\Telegram` facade is the single entry point to every
Bot API method. Each method returns a fluent, immutable object
(every setter returns a new cloned instance), so you can reuse a
request as a "template" without worrying about accidental mutation.

```php
use Aicrion\Tandroid\Api\Telegram;

Telegram::message()
    ->to($chatId)
    ->text('Hello, world')
    ->replyTo($messageId)
    ->markup($keyboard->render()['reply_markup'])
    ->send();
```

`Telegram::configure($httpClient, $token)` is called automatically
by `Kernel::boot()` — in your own application code you'll almost
never need to call it manually.

## Full Method Table

| Facade method | Returns | Corresponding Bot API |
|---|---|---|
| `message()` | `SendMessageRequest` | `sendMessage` |
| `edit($chatId, $messageId)` | `MessageEditor` | `editMessageText`/`editMessageReplyMarkup` |
| `message_($fromChatId, $messageId)` | `MessageForwarder` | `forwardMessage`/`copyMessage` |
| `reaction($chatId, $messageId)` | `ReactionRequest` | `setMessageReaction` |
| `photo()` | `Media\SendPhotoRequest` | `sendPhoto` |
| `video()` | `Media\SendVideoRequest` | `sendVideo` |
| `document()` | `Media\SendDocumentRequest` | `sendDocument` |
| `audio()` | `Media\SendAudioRequest` | `sendAudio` |
| `voice()` | `Media\SendVoiceRequest` | `sendVoice` |
| `animation()` | `Media\SendAnimationRequest` | `sendAnimation` |
| `videoNote()` | `Media\SendVideoNoteRequest` | `sendVideoNote` |
| `location()` | `Media\SendLocationRequest` | `sendLocation` |
| `venue()` | `Media\SendVenueRequest` | `sendVenue` |
| `contact()` | `Media\SendContactRequest` | `sendContact` |
| `dice()` | `Media\SendDiceRequest` | `sendDice` |
| `poll()` | `Media\SendPollRequest` | `sendPoll` |
| `mediaGroup()` | `Media\MediaGroupRequest` | `sendMediaGroup` |
| `chatAction()` | `ChatActionRequest` | `sendChatAction` |
| `chat($chatId)` | `Admin\ChatAdmin` | ban/restrict/promote/pin/... |
| `inviteLinks($chatId)` | `Admin\ChatInviteLinkManager` | create/edit/revoke invite link |
| `joinRequests()` | `Invite\JoinRequestManager` | approve/decline chat join request |
| `forum($chatId)` | `Forum\ForumTopicManager` | forum topic management |
| `business($businessConnectionId)` | `Business\BusinessAccount` | Telegram Business API |
| `gifts()` | `Gifts\GiftManager` | send/manage gifts |
| `stickers()` | `Stickers\StickerSetManager` | sticker set management |
| `invoice()` | `Payments\InvoiceRequest` | `sendInvoice` |
| `preCheckout()` | `Payments\PreCheckoutHandler` | `answerPreCheckoutQuery` |
| `starTransactions()` | `Payments\StarTransactions` | Telegram Stars transactions |
| `inline($inlineQueryId)` | `Inline\InlineQueryAnswer` | `answerInlineQuery` |
| `webApp($webAppQueryId)` | `Inline\WebAppQueryAnswer` | `answerWebAppQuery` |
| `guestQuery($guestQueryId)` | `Guest\GuestQueryAnswer` | `answerGuestQuery` (Guest mode — see [Advanced Features](16-advanced-features.md)) |
| `callback($callbackQueryId)` | `CallbackQueryAnswer` | `answerCallbackQuery` |
| `commands()` | `Menu\CommandMenu` | `setMyCommands`/`getMyCommands` |
| `chatInfo($chatId)` | `Info\ChatInfo` | `getChat`/`getChatMember`/... |
| `botInfo()` | `Info\BotInfo` | `getMe`/`setMyName`/... |
| `webhook()` | `Kernel\Transport\WebhookManager` | `setWebhook`/`deleteWebhook`/`getWebhookInfo` |
| `polling()` | `Kernel\Transport\PollingManager` | `getUpdates` |
| `managedAccess()` | `Managed\ManagedBotAccess` | manage access for managed bots (see [Advanced Features](16-advanced-features.md)) |

## Example: Send, Edit, and React

```php
$sent = Telegram::message()->to($chatId)->text('Processing... ⏳')->send();

Telegram::edit($chatId, $sent['result']['message_id'])
    ->text('Done ✅')
    ->send(); // see MessageEditor for the exact signature

Telegram::reaction($chatId, $sent['result']['message_id'])
    ->emoji('👍')
    ->send();
```

## Example: Chat Administration

```php
Telegram::chat($chatId)->ban(userId: 123456);
Telegram::chat($chatId)->promote(userId: 123456, canPinMessages: true);
Telegram::inviteLinks($chatId)->create(name: 'VIP Invite', memberLimit: 50);
```

## Example: Answering a Callback Query with a Custom Toast

Every callback query is answered by default by `ActivityManager`
with an empty response (`Telegram::callback($id)->send()`) to stop
the client-side loading spinner. For a custom toast, call this
yourself from your Activity — the subsequent automatic reply from
`ActivityManager` is harmless since Telegram ignores a duplicate
`answerCallbackQuery`:

```php
Telegram::callback($update->raw['callback_query']['id'])
    ->toast('Option saved successfully', asAlert: false)
    ->send();
```

## Raw Access (Escape Hatch)

If a Bot API method doesn't yet have a dedicated wrapper, every
internal request class uses
`Symfony\Contracts\HttpClient\HttpClientInterface` — you can use the
same HTTP client directly:

```php
$kernel->httpClient()->request('POST', "https://api.telegram.org/bot{$token}/someNewMethod", [
    'json' => ['chat_id' => $chatId, /* ... */],
]);
```
