<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Widget;

/**
 * Paginated list of selectable items, rendered as one inline button
 * per row plus a Prev/Next navigation row. Uses cursor-based paging
 * (opaque cursor token in callback_data) rather than raw offsets,
 * so it stays correct even if the underlying dataset changes between
 * page loads.
 */
final class ListWidget implements WidgetInterface
{
    /** @var list<array{label: string, activity: string, payload: array}> */
    private array $items = [];

    private ?string $prevCursor = null;

    private ?string $nextCursor = null;

    private function __construct(
        public readonly string $navigateActivity,
    ) {}

    public static function make(string $navigateActivity): self
    {
        return new self($navigateActivity);
    }

    public function item(string $label, array $payload = []): self
    {
        $clone = clone $this;
        $clone->items[] = ['label' => $label, 'payload' => $payload];

        return $clone;
    }

    public function cursors(?string $prev, ?string $next): self
    {
        $clone = clone $this;
        $clone->prevCursor = $prev;
        $clone->nextCursor = $next;

        return $clone;
    }

    public function render(): array
    {
        $rows = array_map(
            fn (array $item) => [Button::action($item['label'], to: $this->navigateActivity, payload: $item['payload'])],
            $this->items,
        );

        $navRow = array_filter([
            $this->prevCursor !== null ? Button::action('« قبلی', to: $this->navigateActivity, payload: ['cursor' => $this->prevCursor]) : null,
            $this->nextCursor !== null ? Button::action('بعدی »', to: $this->navigateActivity, payload: ['cursor' => $this->nextCursor]) : null,
        ]);

        if ($navRow !== []) {
            $rows[] = array_values($navRow);
        }

        $keyboard = array_reduce($rows, fn (Keyboard $kb, array $row) => $kb->row(...$row), Keyboard::inline());

        return $keyboard->render();
    }
}