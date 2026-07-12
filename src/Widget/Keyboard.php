<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Widget;

/**
 * Fluent builder for inline or reply keyboards. Rows are composed
 * top-to-bottom, buttons left-to-right, mirroring a LinearLayout
 * with vertical orientation nesting horizontal rows.
 */
final class Keyboard implements WidgetInterface
{
    /** @var list<list<Button>> */
    private array $rows = [];

    private function __construct(
        public readonly bool $inline,
    ) {}

    public static function inline(): self
    {
        return new self(inline: true);
    }

    public static function reply(): self
    {
        return new self(inline: false);
    }

    public function row(Button ...$buttons): self
    {
        $clone = clone $this;
        $clone->rows[] = array_values($buttons);

        return $clone;
    }

    public static function requestContact(string $label = 'ارسال شماره تماس'): self
    {
        return self::reply()->row(Button::requestContact($label));
    }

    public function render(): array
    {
        $matrix = array_map(
            static fn (array $row): array => array_map(
                static function (Button $button): array {
                    return array_filter([
                        'text' => $button->label,
                        'callback_data' => $button->resolveCallbackData(),
                        'url' => $button->url,
                        'request_contact' => $button->requestContact ?: null,
                    ], static fn ($v) => $v !== null);
                },
                $row,
            ),
            $this->rows,
        );

        $key = $this->inline ? 'inline_keyboard' : 'keyboard';

        return [
            'reply_markup' => [$key => $matrix],
        ];
    }
}