<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api\Media;

final class SendVideoNoteRequest extends MediaRequest
{
    protected function method(): string
    {
        return 'sendVideoNote';
    }

    protected function mediaField(): string
    {
        return 'video_note';
    }
}
