<?php

declare(strict_types=1);

namespace Greeter;

use Aicrion\Tandroid\Activity\BotActivity;
use Aicrion\Tandroid\Activity\NavigationRequest;
use Aicrion\Tandroid\Attribute\IntentFilter;
use Aicrion\Tandroid\Intent\Intent;
use Aicrion\Tandroid\Remote\RemoteIntent;
use Aicrion\Tandroid\View\View;

/**
 * Demonstrates a bot-to-bot workflow: this Activity receives a
 * request from a human, then relays a RemoteIntent to a specialist
 * bot (e.g. @copywriter_bot) instead of handling it locally —
 * mirroring the multi-agent relay pattern enabled by Bot API 10.0.
 */
#[IntentFilter(action: 'RELAY_TO_SPECIALIST', pattern: '/^\/ask /')]
final class RelayActivity extends BotActivity
{
    public function onCreate(Intent $intent): ?NavigationRequest
    {
        $remote = RemoteIntent::to('copywriter_bot', 'DRAFT_REQUEST')
            ->with('brief', $intent->getExtra('raw_text'))
            ->with('reply_to_chat', $this->update->chatId);

        // BotToBotClient::send($remote) would be dispatched here by
        // the framework's outbound pipeline.

        $this->setContentView(View::message('درخواست شما به دستیار متخصص ارسال شد ⏳'));

        return null;
    }
}
