<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api\Media;

final class SendVoiceRequest extends MediaRequest
{
    protected function method(): string
    {
        return 'sendVoice';
    }

    protected function mediaField(): string
    {
        return 'voice';
    }
}