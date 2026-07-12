<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\View;

use Aicrion\Tandroid\Widget\WidgetInterface;

/**
 * Renderable payload of an Activity. Composable and immutable — every
 * mutator returns a new instance, similar to how Compose treats
 * immutable UI state. Ultimately compiles down to a Telegram sendMessage
 * payload (text + reply_markup + parse_mode).
 */
final class View
{
    /** @var list<WidgetInterface> */
    private array $widgets = [];

    private function __construct(
        public readonly string $text,
        public readonly ?ParseMode $parseMode = ParseMode::Plain,
    ) {}

    public static function message(string $text, ?ParseMode $parseMode = ParseMode::Plain): self
    {
        return new self($text, $parseMode);
    }

    public function attach(WidgetInterface $widget): self
    {
        $clone = clone $this;
        $clone->widgets[] = $widget;

        return $clone;
    }

    public function withKeyboard(WidgetInterface $keyboard): self
    {
        return $this->attach($keyboard);
    }

    /**
     * Flattens the view tree into the final Telegram API payload.
     *
     * @return array<string, mixed>
     */
    public function render(): array
    {
        $payload = [
            'text' => $this->text,
            'parse_mode' => $this->parseMode?->value,
        ];

        foreach ($this->widgets as $widget) {
            $payload = [...$payload, ...$widget->render()];
        }

        return $payload;
    }
}