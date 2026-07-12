<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Widget;

/**
 * Multi-step form widget with an internal state machine. Each step
 * declares a field name, prompt, and validator; the widget tracks
 * the current step index and collected answers, persisted between
 * requests by the owning Activity (typically via BackStackEntry
 * extras or the framework's cache pool).
 */
final class FormWidget implements WidgetInterface
{
    /** @var list<array{name: string, prompt: string, validator: ?\Closure}> */
    private array $steps = [];

    private array $answers = [];

    private int $currentStep = 0;

    private function __construct() {}

    public static function make(): self
    {
        return new self();
    }

    public function step(string $name, string $prompt, ?\Closure $validator = null): self
    {
        $clone = clone $this;
        $clone->steps[] = ['name' => $name, 'prompt' => $prompt, 'validator' => $validator];

        return $clone;
    }

    public function withAnswers(array $answers, int $currentStep): self
    {
        $clone = clone $this;
        $clone->answers = $answers;
        $clone->currentStep = $currentStep;

        return $clone;
    }

    public function submit(string $value): FormSubmissionResult
    {
        $step = $this->steps[$this->currentStep] ?? null;

        if ($step === null) {
            return FormSubmissionResult::complete($this->answers);
        }

        if ($step['validator'] !== null && !($step['validator'])($value)) {
            return FormSubmissionResult::invalid($step['prompt']);
        }

        $answers = [...$this->answers, $step['name'] => $value];
        $nextStep = $this->currentStep + 1;

        if ($nextStep >= count($this->steps)) {
            return FormSubmissionResult::complete($answers);
        }

        return FormSubmissionResult::next($answers, $nextStep, $this->steps[$nextStep]['prompt']);
    }

    public function render(): array
    {
        $step = $this->steps[$this->currentStep] ?? null;

        return [
            'text' => $step['prompt'] ?? 'فرم تکمیل شد.',
        ];
    }
}