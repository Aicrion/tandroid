<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Attribute;

use Attribute;

/**
 * Registers a BroadcastReceiver against a system-level event class,
 * mirroring Android's <receiver> declarations.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class BroadcastFilter
{
    public function __construct(
        public readonly string $event,
    ) {}
}