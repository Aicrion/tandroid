<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\View\Rich;

final class QuoteBlock implements RichBlockInterface
{
    public function __construct(
        private readonly string $text,
        private readonly ?string $author = null,
    ) {}

    public function render(): array
    {
        return array_filter([
            'type' => 'quote',
            'text' => $this->text,
            'author' => $this->author,
        ], static fn ($v) => $v !== null);
    }
}