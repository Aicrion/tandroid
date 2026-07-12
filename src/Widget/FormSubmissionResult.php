<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Widget;

final class FormSubmissionResult
{
    private function __construct(
        public readonly bool $isComplete,
        public readonly bool $isInvalid,
        public readonly array $answers = [],
        public readonly int $nextStep = 0,
        public readonly ?string $nextPrompt = null,
        public readonly ?string $error = null,
    ) {}

    public static function next(array $answers, int $nextStep, string $prompt): self
    {
        return new self(isComplete: false, isInvalid: false, answers: $answers, nextStep: $nextStep, nextPrompt: $prompt);
    }

    public static function complete(array $answers): self
    {
        return new self(isComplete: true, isInvalid: false, answers: $answers);
    }

    public static function invalid(string $prompt): self
    {
        return new self(isComplete: false, isInvalid: true, error: 'مقدار وارد شده نامعتبر است.', nextPrompt: $prompt);
    }
}