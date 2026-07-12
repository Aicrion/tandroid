<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class RequiresAuth
{
    public function __construct(
        public readonly bool $required = true,
        public readonly ?string $role = null,
    ) {}
}