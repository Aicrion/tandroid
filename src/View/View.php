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

    private bool $deletePreviousMessage = false;

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
     * Opt-in: asks Kernel to delete the bot's previous message in
     * this chat right before delivering this View, instead of
     * leaving it in the chat history — useful for e.g. an
     * IntentFilter(action: 'MAIN') Activity reached again via
     * `/start`, where every re-entry would otherwise pile up a new
     * message on top of the old one(s).
     *
     * Purely best-effort: if there is no previous message on record
     * for this chat, or Telegram refuses the delete (message already
     * gone, or older than Telegram's ~48h delete window), Kernel
     * silently skips the delete and sends this View as a normal new
     * message — it never blocks or fails the reply because of this.
     *
     * See "Deleting the Previous Message" in
     * docs/guide/views-and-widgets.md for the full behavior and caveats.
     */
    public function deletePreviousMessage(bool $value = true): self
    {
        $clone = clone $this;
        $clone->deletePreviousMessage = $value;

        return $clone;
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
            'delete_previous_message' => $this->deletePreviousMessage,
        ];

        foreach ($this->widgets as $widget) {
            $payload = [...$payload, ...$widget->render()];
        }

        return $payload;
    }
}