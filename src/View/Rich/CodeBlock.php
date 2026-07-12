<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\View\Rich;

final class CodeBlock implements RichBlockInterface
{
    public function __construct(
        private readonly string $code,
        private readonly ?string $language = null,
    ) {}

    public function render(): array
    {
        return array_filter([
            'type' => 'code',
            'code' => $this->code,
            'language' => $this->language,
        ], static fn ($v) => $v !== null);
    }
}
