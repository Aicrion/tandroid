<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Widget;

/**
 * Multi-select checkbox group rendered as toggleable inline buttons.
 * Current selection state is encoded directly into callback_data so
 * the group is fully stateless server-side between taps.
 */
final class CheckboxGroupWidget implements WidgetInterface
{
    /** @var array<string, string> option value => label */
    private array $options = [];

    /** @var list<string> currently checked option values */
    private array $checked = [];

    private function __construct(
        public readonly string $name,
    ) {}

    public static function make(string $name): self
    {
        return new self($name);
    }

    public function option(string $value, string $label): self
    {
        $clone = clone $this;
        $clone->options[$value] = $label;

        return $clone;
    }

    public function checkedValues(array $values): self
    {
        $clone = clone $this;
        $clone->checked = $values;

        return $clone;
    }

    public function render(): array
    {
        $rows = [];

        foreach ($this->options as $value => $label) {
            $isChecked = in_array($value, $this->checked, strict: true);
            $prefix = $isChecked ? '✅ ' : '⬜️ ';

            $rows[] = [[
                'text' => $prefix . $label,
                'callback_data' => json_encode([
                    'w' => 'checkbox',
                    'n' => $this->name,
                    'v' => $value,
                    'c' => !$isChecked,
                ], JSON_THROW_ON_ERROR),
            ]];
        }

        return [
            'reply_markup' => ['inline_keyboard' => $rows],
        ];
    }
}