<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\View\Rich;

final class ListBlock implements RichBlockInterface
{
    /** @var list<string> */
    private array $items = [];

    public function __construct(
        private readonly bool $ordered = false,
    ) {}

    public function item(string $text): self
    {
        $clone = clone $this;
        $clone->items[] = $text;

        return $clone;
    }

    public function render(): array
    {
        return [
            'type' => $this->ordered ? 'ordered_list' : 'list',
            'items' => $this->items,
        ];
    }
}