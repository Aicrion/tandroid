<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\View\Rich;

final class TableBlock implements RichBlockInterface
{
    /** @var list<list<string>> */
    private array $rows = [];

    public function __construct(
        private readonly array $headers,
    ) {}

    public function row(string ...$cells): self
    {
        $clone = clone $this;
        $clone->rows[] = array_values($cells);

        return $clone;
    }

    public function render(): array
    {
        return [
            'type' => 'table',
            'headers' => $this->headers,
            'rows' => $this->rows,
        ];
    }
}