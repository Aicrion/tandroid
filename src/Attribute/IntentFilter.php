<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Attribute;

use Attribute;

/**
 * Declares which Intent(s) an Activity is capable of handling.
 * Conceptually identical to <intent-filter> in AndroidManifest.xml.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class IntentFilter
{
    /**
     * @param string $action The action name this Activity responds to (e.g. "MAIN", "VIEW_PROFILE").
     * @param string|null $category Optional category grouping, e.g. "LAUNCHER".
     * @param string|null $pattern Optional regex/command pattern to match against raw text or callback_data.
     * @param int $priority Higher priority filters are matched first when multiple Activities qualify.
     */
    public function __construct(
        public readonly string $action,
        public readonly ?string $category = null,
        public readonly ?string $pattern = null,
        public readonly int $priority = 0,
    ) {}
}