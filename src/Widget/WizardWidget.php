<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Widget;

/**
 * A richer alternative to FormWidget for multi-page flows that need
 * explicit Back/Next navigation buttons (not just linear text
 * prompts) — e.g. a settings wizard where each page is its own View
 * with widgets, and the user can freely move both directions. Page
 * state (index, collected data) is passed in from the owning
 * Activity, keeping WizardWidget itself stateless/rerenderable.
 */
final class WizardWidget implements WidgetInterface
{
    /** @var list<WizardPage> */
    private array $pages = [];

    private int $currentIndex = 0;

    private function __construct(
        public readonly string $ownerActivity,
    ) {}

    public static function make(string $ownerActivity): self
    {
        return new self($ownerActivity);
    }

    public function page(WizardPage $page): self
    {
        $clone = clone $this;
        $clone->pages[] = $page;

        return $clone;
    }

    public function atIndex(int $index): self
    {
        $clone = clone $this;
        $clone->currentIndex = max(0, min($index, count($this->pages) - 1));

        return $clone;
    }

    public function currentPage(): ?WizardPage
    {
        return $this->pages[$this->currentIndex] ?? null;
    }

    public function render(): array
    {
        $page = $this->currentPage();

        if ($page === null) {
            return ['text' => 'ویزارد خالی است.'];
        }

        $navRow = array_filter([
            $this->currentIndex > 0
                ? Button::action('« قبلی', to: $this->ownerActivity, payload: ['wizard_step' => $this->currentIndex - 1])
                : null,
            $this->currentIndex < count($this->pages) - 1
                ? Button::action('بعدی »', to: $this->ownerActivity, payload: ['wizard_step' => $this->currentIndex + 1])
                : null,
        ]);

        $keyboard = Keyboard::inline();

        foreach ($page->buttons as $row) {
            $keyboard = $keyboard->row(...$row);
        }

        if ($navRow !== []) {
            $keyboard = $keyboard->row(...array_values($navRow));
        }

        return [
            'text' => $page->text,
            ...$keyboard->render(),
        ];
    }
}
