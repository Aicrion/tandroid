<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Api\Media;

final class SendDocumentRequest extends MediaRequest
{
    protected function method(): string
    {
        return 'sendDocument';
    }

    protected function mediaField(): string
    {
        return 'document';
    }
}