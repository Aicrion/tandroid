<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api\Media;

final class SendVideoRequest extends MediaRequest
{
    protected function method(): string
    {
        return 'sendVideo';
    }

    protected function mediaField(): string
    {
        return 'video';
    }
}