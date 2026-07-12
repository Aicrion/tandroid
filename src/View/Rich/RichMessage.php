<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\View\Rich;

use Aicrion\Tandroid\Widget\WidgetInterface;

/**
 * Structured alternative to View for Bot API 10.1 Rich Messages.
 * Composes an ordered sequence of RichBlocks (tables, lists, quotes,
 * code snippets, maps, slideshows) instead of a single flat text
 * string — the natural fit for AI-assistant bots that need to render
 * comparison tables or step lists without hand-rolled Markdown.
 */
final class RichMessage
{
    /** @var list<RichBlockInterface> */
    private array $blocks = [];

    private array $widgets = [];

    public static function make(): self
    {
        return new self();
    }

    public function block(RichBlockInterface $block): self
    {
        $clone = clone $this;
        $clone->blocks[] = $block;

        return $clone;
    }

    public function attach(WidgetInterface $widget): self
    {
        $clone = clone $this;
        $clone->widgets[] = $widget;

        return $clone;
    }

    public function render(): array
    {
        $payload = [
            'content' => array_map(static fn (RichBlockInterface $b) => $b->render(), $this->blocks),
        ];

        foreach ($this->widgets as $widget) {
            $payload = [...$payload, ...$widget->render()];
        }

        return $payload;
    }
}
