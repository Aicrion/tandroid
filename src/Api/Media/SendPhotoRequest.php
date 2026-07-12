<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api\Media;

final class SendPhotoRequest extends MediaRequest
{
    protected function method(): string
    {
        return 'sendPhoto';
    }

    protected function mediaField(): string
    {
        return 'photo';
    }
}
