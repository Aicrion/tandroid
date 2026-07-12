<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\View\Rich;

final class SlideshowBlock implements RichBlockInterface
{
    /** @var list<string> file_ids or URLs */
    private array $slides = [];

    public function slide(string $media): self
    {
        $clone = clone $this;
        $clone->slides[] = $media;

        return $clone;
    }

    public function render(): array
    {
        return [
            'type' => 'slideshow',
            'slides' => $this->slides,
        ];
    }
}
