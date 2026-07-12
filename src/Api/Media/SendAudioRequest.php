<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api\Media;

final class SendAudioRequest extends MediaRequest
{
    protected function method(): string
    {
        return 'sendAudio';
    }

    protected function mediaField(): string
    {
        return 'audio';
    }
}
