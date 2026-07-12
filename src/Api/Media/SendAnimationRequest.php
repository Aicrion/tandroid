<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api\Media;

final class SendAnimationRequest extends MediaRequest
{
    protected function method(): string
    {
        return 'sendAnimation';
    }

    protected function mediaField(): string
    {
        return 'animation';
    }
}