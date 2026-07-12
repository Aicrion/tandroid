<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Activity;

use Aicrion\Tandroid\Intent\Intent;

/**
 * Returned by BotActivity::startActivity()/finishWithResult() and
 * consumed by the ActivityManager to know how to mutate the
 * per-user back-stack after the current lifecycle call completes.
 */
final class NavigationRequest
{
    private function __construct(
        public readonly ?Intent $intent,
        public readonly bool $isFinish,
        public readonly array $result = [],
    ) {}

    public static function navigate(Intent $intent): self
    {
        return new self($intent, isFinish: false);
    }

    public static function finish(array $result = []): self
    {
        return new self(null, isFinish: true, result: $result);
    }
}